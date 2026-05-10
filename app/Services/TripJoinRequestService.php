<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripJoinRequest;
use App\Models\TripPassengerRoutePoint;
use App\Models\TripParticipant;
use App\Models\TripPayment;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripJoinRequestService
{
    public function listForTrip(User $actor, Trip $trip)
    {
        $this->ensureCanManageTripRequests($actor, $trip);

        return TripJoinRequest::query()
            ->with(['user', 'responder', 'routePoint'])
            ->where('trip_id', $trip->id)
            ->latest('id')
            ->paginate(15);
    }

    public function submitRequest(User $passenger, Trip $trip, ?string $note = null, array $routePointData = []): TripJoinRequest
    {
        $baseTrip = $this->resolveBaseTrip($trip);
        $this->ensureCanRequestJoin($passenger, $baseTrip);

        return DB::transaction(function () use ($passenger, $baseTrip, $note, $routePointData): TripJoinRequest {
            $existing = TripJoinRequest::query()
                ->where('trip_id', $baseTrip->id)
                ->where('user_id', $passenger->id)
                ->first();

            if ($existing && $existing->status === 'approved') {
                throw ValidationException::withMessages([
                    'request' => 'You already joined this trip.',
                ]);
            }

            if ($existing && $existing->status === 'pending') {
                throw ValidationException::withMessages([
                    'request' => 'You already sent a join request.',
                ]);
            }

            if ($existing) {
                $existing->update([
                    'status' => 'pending',
                    'request_note' => $note,
                    'response_note' => null,
                    'responded_by' => null,
                    'responded_at' => null,
                ]);
                $request = $existing->refresh();
            } else {
                $request = TripJoinRequest::query()->create([
                    'trip_id' => $baseTrip->id,
                    'user_id' => $passenger->id,
                    'status' => 'pending',
                    'request_note' => $note,
                ]);
            }

            $this->upsertRoutePointForRequest($request, $passenger, $baseTrip, $routePointData);

            UserNotification::query()->create([
                'user_id' => $baseTrip->driver_id,
                'type' => 'trip',
                'title' => 'New Join Request',
                'message' => "{$passenger->name} requested to join trip #{$baseTrip->id}.",
                'related_type' => 'trip_join_request',
                'related_id' => $request->id,
                'is_read' => false,
            ]);

            return $request;
        });
    }

    public function cancelRequest(User $passenger, TripJoinRequest $joinRequest): TripJoinRequest
    {
        if ($joinRequest->user_id !== $passenger->id) {
            abort(403);
        }

        if ($joinRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'Only pending request can be cancelled.',
            ]);
        }

        $joinRequest->update([
            'status' => 'cancelled',
            'responded_by' => $passenger->id,
            'responded_at' => now(),
        ]);
        $joinRequest->routePoint?->update(['status' => 'cancelled']);

        return $joinRequest->refresh();
    }

    public function respond(User $actor, TripJoinRequest $joinRequest, string $action, ?string $responseNote = null): TripJoinRequest
    {
        $joinRequest->loadMissing('trip', 'user');
        $trip = $this->resolveBaseTrip($joinRequest->trip);
        $this->ensureCanManageTripRequests($actor, $trip);

        if ($joinRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'Only pending request can be processed.',
            ]);
        }

        if (! in_array($action, ['approve', 'reject'], true)) {
            throw ValidationException::withMessages([
                'action' => 'Invalid request action.',
            ]);
        }

        return DB::transaction(function () use ($actor, $joinRequest, $action, $responseNote, $trip): TripJoinRequest {
            if ($action === 'approve') {
                $this->assertJoinableTrip($trip);
                $this->assertSeatsAvailable($trip);
                $this->assertNoProcessedPayments($trip);
                $this->attachPassengerToTripGroup($trip, $joinRequest->user_id);
                $this->linkAcceptedRoutePoint($joinRequest, $trip);
            } else {
                $joinRequest->routePoint?->update(['status' => 'rejected']);
            }

            $joinRequest->update([
                'status' => $action === 'approve' ? 'approved' : 'rejected',
                'response_note' => $responseNote,
                'responded_by' => $actor->id,
                'responded_at' => now(),
            ]);

            UserNotification::query()->create([
                'user_id' => $joinRequest->user_id,
                'type' => 'trip',
                'title' => $action === 'approve' ? 'Join Request Approved' : 'Join Request Rejected',
                'message' => $action === 'approve'
                    ? "Your request for trip #{$trip->id} was approved."
                    : "Your request for trip #{$trip->id} was rejected.",
                'related_type' => 'trip_join_request',
                'related_id' => $joinRequest->id,
                'is_read' => false,
            ]);

            return $joinRequest->refresh();
        });
    }

    public function setOpenState(User $actor, Trip $trip, bool $open): Trip
    {
        $baseTrip = $this->resolveBaseTrip($trip);
        $this->ensureCanManageTripRequests($actor, $baseTrip);

        if ($baseTrip->visibility !== 'public') {
            throw ValidationException::withMessages([
                'trip' => 'Only public trip can be opened or closed for requests.',
            ]);
        }

        if ($open) {
            $this->assertSeatsAvailable($baseTrip);
        }

        $targetTrips = Trip::query()
            ->where('id', $baseTrip->id)
            ->orWhere('parent_trip_id', $baseTrip->id)
            ->get();

        foreach ($targetTrips as $targetTrip) {
            $targetTrip->update(['is_open_for_request' => $open]);
        }

        return $baseTrip->refresh();
    }

    private function ensureCanRequestJoin(User $passenger, Trip $trip): void
    {
        $this->assertJoinableTrip($trip);

        if ($trip->driver_id === $passenger->id) {
            throw ValidationException::withMessages([
                'request' => 'Driver cannot join own trip.',
            ]);
        }

        $isAlreadyPassenger = TripParticipant::query()
            ->where('trip_id', $trip->id)
            ->where('user_id', $passenger->id)
            ->exists();

        if ($isAlreadyPassenger) {
            throw ValidationException::withMessages([
                'request' => 'You already joined this trip.',
            ]);
        }

        $this->assertSeatsAvailable($trip);
    }

    private function assertJoinableTrip(Trip $trip): void
    {
        if ($trip->visibility !== 'public') {
            throw ValidationException::withMessages([
                'request' => 'This trip is private.',
            ]);
        }

        if (! $trip->is_open_for_request) {
            throw ValidationException::withMessages([
                'request' => 'Public joining is closed for this trip.',
            ]);
        }

        if ($trip->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'request' => 'Trip is no longer open for join request.',
            ]);
        }

        if ($trip->trip_datetime && $trip->trip_datetime->isPast()) {
            throw ValidationException::withMessages([
                'request' => 'Trip already passed.',
            ]);
        }
    }

    private function assertSeatsAvailable(Trip $trip): void
    {
        if (! $trip->seat_limit) {
            return;
        }

        $taken = (int) TripParticipant::query()
            ->where('trip_id', $trip->id)
            ->where('is_driver', false)
            ->count();

        if ($taken >= (int) $trip->seat_limit) {
            throw ValidationException::withMessages([
                'request' => 'Trip is full.',
            ]);
        }
    }

    private function attachPassengerToTripGroup(Trip $baseTrip, int $passengerId): void
    {
        $tripGroup = Trip::query()
            ->where('id', $baseTrip->id)
            ->orWhere('parent_trip_id', $baseTrip->id)
            ->with(['participants', 'payments'])
            ->get();

        foreach ($tripGroup as $trip) {
            $userIds = $trip->participants->pluck('user_id')->merge([$passengerId])->unique()->values();
            $this->resyncTripSplit($trip, $userIds);
        }

        if ($baseTrip->seat_limit) {
            $taken = (int) TripParticipant::query()
                ->where('trip_id', $baseTrip->id)
                ->where('is_driver', false)
                ->count();

            if ($taken >= (int) $baseTrip->seat_limit) {
                Trip::query()
                    ->where('id', $baseTrip->id)
                    ->orWhere('parent_trip_id', $baseTrip->id)
                    ->update(['is_open_for_request' => false]);
            }
        }
    }

    private function resyncTripSplit(Trip $trip, Collection $participantIds): void
    {
        $participantIds = $participantIds->map(fn ($id) => (int) $id)->unique()->values();
        $includeDriverInSplit = $participantIds->contains((int) $trip->driver_id);
        $splitCount = $this->resolveSplitCountForTrip($trip, $participantIds->count(), $includeDriverInSplit);
        $perPerson = $this->farePerPerson((float) $trip->fare_total, $splitCount);
        $amounts = collect(range(1, max(1, $participantIds->count())))->map(fn () => $perPerson);

        TripParticipant::query()->where('trip_id', $trip->id)->delete();
        TripPayment::query()->where('trip_id', $trip->id)->delete();

        foreach ($participantIds as $index => $userId) {
            $fareAmount = (float) $amounts->get($index, 0);

            TripParticipant::query()->create([
                'trip_id' => $trip->id,
                'user_id' => $userId,
                'is_driver' => $userId === $trip->driver_id,
                'fare_amount' => $fareAmount,
                'attendance_status' => 'joined',
            ]);

            TripPayment::query()->create([
                'trip_id' => $trip->id,
                'user_id' => $userId,
                'amount_due' => $fareAmount,
                'payment_status' => 'unpaid',
            ]);
        }

        $trip->update([
            'participant_count' => $participantIds->count(),
            'fare_per_person' => $perPerson,
        ]);
    }

    private function resolveSplitCountForTrip(Trip $trip, int $activeCount, bool $includeDriverInSplit): int
    {
        if ($trip->visibility === 'public' && $trip->seat_limit && $trip->seat_limit > 0) {
            return ((int) $trip->seat_limit) + ($includeDriverInSplit ? 1 : 0);
        }

        return max(1, $activeCount);
    }

    private function farePerPerson(float $fareTotal, int $splitCount): float
    {
        if ($splitCount <= 0) {
            return 0;
        }

        return round($fareTotal / $splitCount, 2);
    }

    private function upsertRoutePointForRequest(TripJoinRequest $request, User $passenger, Trip $trip, array $data): void
    {
        $payload = $this->routePointPayload($passenger, $trip, $data);

        TripPassengerRoutePoint::query()->updateOrCreate(
            [
                'trip_join_request_id' => $request->id,
            ],
            array_merge($payload, [
                'trip_id' => $trip->id,
                'user_id' => $passenger->id,
                'trip_participant_id' => null,
                'status' => 'requested',
            ])
        );
    }

    private function routePointPayload(User $passenger, Trip $trip, array $data): array
    {
        $pickupMode = (string) ($data['pickup_mode'] ?? 'default');
        $dropoffMode = (string) ($data['dropoff_mode'] ?? 'default');
        $pickupCustom = $pickupMode === 'custom' && trim((string) ($data['pickup_name'] ?? '')) !== '';
        $dropoffCustom = $dropoffMode === 'custom' && trim((string) ($data['dropoff_name'] ?? '')) !== '';

        $payload = [
            'pickup_name' => $pickupCustom ? trim((string) $data['pickup_name']) : (string) ($trip->pickup_name ?: 'Pickup'),
            'pickup_latitude' => $pickupCustom ? ($data['pickup_latitude'] ?? null) : $trip->pickup_latitude,
            'pickup_longitude' => $pickupCustom ? ($data['pickup_longitude'] ?? null) : $trip->pickup_longitude,
            'dropoff_name' => $dropoffCustom ? trim((string) $data['dropoff_name']) : (string) ($trip->destination_name ?: 'Destination'),
            'dropoff_latitude' => $dropoffCustom ? ($data['dropoff_latitude'] ?? null) : $trip->destination_latitude,
            'dropoff_longitude' => $dropoffCustom ? ($data['dropoff_longitude'] ?? null) : $trip->destination_longitude,
            'uses_default_pickup' => ! $pickupCustom,
            'uses_default_dropoff' => ! $dropoffCustom,
            'requested_pickup_time' => $data['requested_pickup_time'] ?? null,
            'detour_distance_km' => $data['detour_distance_km'] ?? null,
            'fare_override_amount' => $data['fare_override_amount'] ?? null,
        ];

        $fit = $this->routeFitFor($trip, $payload);

        return array_merge($payload, $fit, [
            'detour_distance_km' => $payload['detour_distance_km'] ?? $fit['detour_distance_km'] ?? null,
        ]);
    }

    private function routeFitFor(Trip $trip, array $payload): array
    {
        if (($payload['uses_default_pickup'] ?? true) && ($payload['uses_default_dropoff'] ?? true)) {
            return [
                'route_fit_score' => 100,
                'route_fit_label' => 'Uses trip pickup and destination',
                'pickup_distance_km' => 0,
                'dropoff_distance_km' => 0,
                'detour_distance_km' => null,
                'detour_duration_minutes' => null,
            ];
        }

        $pickupDistance = $this->nearestEndpointDistanceKm(
            $payload['pickup_latitude'] ?? null,
            $payload['pickup_longitude'] ?? null,
            $trip
        );
        $dropoffDistance = $this->nearestEndpointDistanceKm(
            $payload['dropoff_latitude'] ?? null,
            $payload['dropoff_longitude'] ?? null,
            $trip
        );

        $availableDistances = collect([$pickupDistance, $dropoffDistance])->filter(fn ($value) => $value !== null)->values();
        if ($availableDistances->isEmpty()) {
            $customCount = (int) (! ($payload['uses_default_pickup'] ?? true)) + (int) (! ($payload['uses_default_dropoff'] ?? true));

            return [
                'route_fit_score' => $customCount > 1 ? 65 : 75,
                'route_fit_label' => 'Custom stop, driver review needed',
                'pickup_distance_km' => $pickupDistance,
                'dropoff_distance_km' => $dropoffDistance,
                'detour_distance_km' => null,
                'detour_duration_minutes' => null,
            ];
        }

        $averageDistance = (float) $availableDistances->avg();
        $score = match (true) {
            $averageDistance <= 1 => 95,
            $averageDistance <= 3 => 85,
            $averageDistance <= 8 => 70,
            default => 55,
        };
        $label = match (true) {
            $score >= 85 => 'Likely near route endpoints',
            $score >= 70 => 'Driver review suggested',
            default => 'Off route, confirm before approve',
        };

        return [
            'route_fit_score' => $score,
            'route_fit_label' => $label,
            'pickup_distance_km' => $pickupDistance,
            'dropoff_distance_km' => $dropoffDistance,
            'detour_distance_km' => null,
            'detour_duration_minutes' => null,
        ];
    }

    private function nearestEndpointDistanceKm(mixed $latitude, mixed $longitude, Trip $trip): ?float
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }

        $pickupDistance = $this->distanceKm((float) $latitude, (float) $longitude, $trip->pickup_latitude, $trip->pickup_longitude);
        $destinationDistance = $this->distanceKm((float) $latitude, (float) $longitude, $trip->destination_latitude, $trip->destination_longitude);
        $distances = collect([$pickupDistance, $destinationDistance])->filter(fn ($value) => $value !== null)->values();

        return $distances->isEmpty() ? null : round((float) $distances->min(), 2);
    }

    private function distanceKm(float $lat1, float $lng1, mixed $lat2, mixed $lng2): ?float
    {
        if ($lat2 === null || $lng2 === null || $lat2 === '' || $lng2 === '') {
            return null;
        }

        $earthRadiusKm = 6371;
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad((float) $lat2);
        $lngTo = deg2rad((float) $lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;
        $angle = 2 * asin(sqrt(
            (sin($latDelta / 2) ** 2) +
            cos($latFrom) * cos($latTo) * (sin($lngDelta / 2) ** 2)
        ));

        return round($earthRadiusKm * $angle, 2);
    }

    private function linkAcceptedRoutePoint(TripJoinRequest $joinRequest, Trip $trip): void
    {
        $participant = TripParticipant::query()
            ->where('trip_id', $trip->id)
            ->where('user_id', $joinRequest->user_id)
            ->first();

        $joinRequest->routePoint?->update([
            'trip_participant_id' => $participant?->id,
            'status' => 'accepted',
        ]);
    }

    private function distributeFare(float $fareTotal, int $participantCount): Collection
    {
        if ($participantCount <= 0) {
            return collect();
        }

        $totalCents = (int) round($fareTotal * 100);
        $baseCents = intdiv($totalCents, $participantCount);
        $remainder = $totalCents - ($baseCents * $participantCount);

        return collect(range(1, $participantCount))->map(function (int $index) use ($baseCents, $remainder): float {
            $cents = $baseCents + ($index <= $remainder ? 1 : 0);
            return $cents / 100;
        });
    }

    private function assertNoProcessedPayments(Trip $baseTrip): void
    {
        $tripIds = Trip::query()
            ->where('id', $baseTrip->id)
            ->orWhere('parent_trip_id', $baseTrip->id)
            ->pluck('id');

        $hasProcessedPayment = TripPayment::query()
            ->whereIn('trip_id', $tripIds)
            ->whereIn('payment_status', ['pending_confirmation', 'paid'])
            ->exists();

        if ($hasProcessedPayment) {
            throw ValidationException::withMessages([
                'request' => 'Cannot approve new passenger after payment processing started.',
            ]);
        }
    }

    private function resolveBaseTrip(Trip $trip): Trip
    {
        if ($trip->is_return_trip && $trip->parentTrip) {
            return $trip->parentTrip;
        }

        return $trip;
    }

    private function ensureCanManageTripRequests(User $actor, Trip $trip): void
    {
        if ($actor->role === 'admin' || $trip->driver_id === $actor->id) {
            return;
        }

        abort(403);
    }
}
