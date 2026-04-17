<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripJoinRequest;
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
            ->with(['user', 'responder'])
            ->where('trip_id', $trip->id)
            ->latest('id')
            ->paginate(15);
    }

    public function submitRequest(User $passenger, Trip $trip, ?string $note = null): TripJoinRequest
    {
        $baseTrip = $this->resolveBaseTrip($trip);
        $this->ensureCanRequestJoin($passenger, $baseTrip);

        return DB::transaction(function () use ($passenger, $baseTrip, $note): TripJoinRequest {
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
