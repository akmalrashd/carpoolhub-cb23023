<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\SavedRoute;
use App\Models\Trip;
use App\Models\TripPassengerRoutePoint;
use App\Models\TripParticipant;
use App\Models\TripPayment;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripService
{
    private static bool $lifecycleSynced = false;

    public function paginateForUser(User $user, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $this->syncLifecycleStatuses();

        $query = $this->baseUserTripsQuery($user)
            ->with([
                'savedRoute',
                'driver',
                'participants.user',
                'joinRequests.user',
                'joinRequests.routePoint',
                'payments.user',
                'returnTrip.savedRoute',
                'returnTrip.participants.user',
                'returnTrip.payments.user',
                'passengerRoutePoints.user',
                'returnTrip.passengerRoutePoints.user',
            ]);

        $this->applyTripIndexFilters($query, $filters);
        $statusFilter = strtolower((string) ($filters['status_filter'] ?? ''));
        if ($statusFilter === 'upcoming') {
            $query->whereIn('status', ['scheduled', 'confirmed']);
        } elseif ($statusFilter === 'completed') {
            $query->whereIn('status', ['recorded', 'completed']);
        } elseif (in_array($statusFilter, ['draft', 'cancelled'], true)) {
            $query->where('status', $statusFilter);
        }

        return $query->latest('trip_datetime')->paginate($perPage);
    }

    public function statusCountsForUser(User $user, array $filters = []): array
    {
        $query = $this->baseUserTripsQuery($user);

        $this->applyTripIndexFilters($query, $filters);

        return [
            'all' => (clone $query)->count(),
            'upcoming' => (clone $query)->whereIn('status', ['scheduled', 'confirmed'])->count(),
            'completed' => (clone $query)->whereIn('status', ['recorded', 'completed'])->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];
    }

    private function baseUserTripsQuery(User $user)
    {
        $query = Trip::query()
            ->whereNull('parent_trip_id');

        if ($user->role !== 'admin') {
            $query->where(function ($builder) use ($user): void {
                $builder->where('driver_id', $user->id)
                    ->orWhereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $user->id));
            });
        }

        return $query;
    }

    private function applyTripIndexFilters($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $from = Carbon::parse((string) $filters['date_from'])->startOfDay();
            $query->where('trip_datetime', '>=', $from);
        }

        if (! empty($filters['date_to'])) {
            $to = Carbon::parse((string) $filters['date_to'])->endOfDay();
            $query->where('trip_datetime', '<=', $to);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', (string) $filters['visibility']);
        }

        if (! empty($filters['trip_search'])) {
            $search = trim((string) $filters['trip_search']);
            $query->where(function ($q) use ($search): void {
                $q->where('trip_ref', 'like', "%{$search}%")
                    ->orWhere('pickup_name', 'like', "%{$search}%")
                    ->orWhere('destination_name', 'like', "%{$search}%")
                    ->orWhereHas('savedRoute', fn ($r) => $r->where('route_name', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn ($d) => $d->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('participants.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }
    }

    public function paginateExplore(User $user, int $perPage = 12, array $filters = []): LengthAwarePaginator
    {
        $this->syncLifecycleStatuses();

        $query = Trip::query()
            ->with(['savedRoute', 'participants', 'driver' => fn ($q) => $q->withoutHeavyMedia(), 'joinRequests' => fn ($joinQuery) => $joinQuery->where('user_id', $user->id)])
            ->whereNull('parent_trip_id')
            ->where('visibility', 'public')
            ->where('is_open_for_request', true)
            ->where('status', 'scheduled')
            ->where('trip_datetime', '>=', now());

        $query->whereRaw(
            '(seat_limit IS NULL OR seat_limit > (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.is_driver = 0))'
        );

        if (! empty($filters['destination'])) {
            $destination = trim((string) $filters['destination']);
            $destinationTerms = collect(preg_split('/[\s,.;\-\/]+/', $destination) ?: [])
                ->map(fn ($term) => trim((string) $term))
                ->filter(fn ($term) => mb_strlen($term) >= 3)
                ->unique()
                ->take(6)
                ->values();

            $query->where(function ($routeTextQuery) use ($destination, $destinationTerms): void {
                $routeTextQuery->where('destination_name', 'like', "%{$destination}%")
                    ->orWhereHas('savedRoute', fn ($savedRouteQuery) => $savedRouteQuery->where('route_name', 'like', "%{$destination}%"));

                foreach ($destinationTerms as $term) {
                    $routeTextQuery
                        ->orWhere('destination_name', 'like', "%{$term}%")
                        ->orWhereHas('savedRoute', fn ($savedRouteQuery) => $savedRouteQuery->where('route_name', 'like', "%{$term}%"));
                }
            });
        }

        if (! empty($filters['pickup'])) {
            $pickup = trim((string) $filters['pickup']);
            $pickupTerms = collect(preg_split('/[\s,.;\-\/]+/', $pickup) ?: [])
                ->map(fn ($term) => trim((string) $term))
                ->filter(fn ($term) => mb_strlen($term) >= 3)
                ->unique()
                ->take(6)
                ->values();

            $query->where(function ($routeTextQuery) use ($pickup, $pickupTerms): void {
                $routeTextQuery->where('pickup_name', 'like', "%{$pickup}%")
                    ->orWhereHas('savedRoute', fn ($savedRouteQuery) => $savedRouteQuery->where('route_name', 'like', "%{$pickup}%"));

                foreach ($pickupTerms as $term) {
                    $routeTextQuery
                        ->orWhere('pickup_name', 'like', "%{$term}%")
                        ->orWhereHas('savedRoute', fn ($savedRouteQuery) => $savedRouteQuery->where('route_name', 'like', "%{$term}%"));
                }
            });
        }

        if (! empty($filters['driver'])) {
            $driver = trim((string) $filters['driver']);
            $query->whereHas('driver', fn ($driverQuery) => $driverQuery->where('name', 'like', "%{$driver}%"));
        }

        if (! empty($filters['date'])) {
            $date = \Illuminate\Support\Carbon::parse((string) $filters['date']);
            $query->whereBetween('trip_datetime', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
        }

        $centerLat = isset($filters['center_lat']) ? (float) $filters['center_lat'] : null;
        $centerLng = isset($filters['center_lng']) ? (float) $filters['center_lng'] : null;
        if ($centerLat !== null && $centerLng !== null) {
            $radiusKm = isset($filters['radius_km']) && (float) $filters['radius_km'] > 0
                ? (float) $filters['radius_km']
                : 5.0;

            $query->where(function ($geoQuery) use ($centerLat, $centerLng, $radiusKm): void {
                $geoQuery
                    ->where(function ($destinationGeo) use ($centerLat, $centerLng, $radiusKm): void {
                        $destinationGeo
                            ->whereNotNull('destination_latitude')
                            ->whereNotNull('destination_longitude')
                            ->whereRaw(
                                '(6371 * acos(cos(radians(?)) * cos(radians(destination_latitude)) * cos(radians(destination_longitude) - radians(?)) + sin(radians(?)) * sin(radians(destination_latitude)))) <= ?',
                                [$centerLat, $centerLng, $centerLat, $radiusKm]
                            );
                    })
                    ->orWhere(function ($pickupGeo) use ($centerLat, $centerLng, $radiusKm): void {
                        $pickupGeo
                            ->whereNotNull('pickup_latitude')
                            ->whereNotNull('pickup_longitude')
                            ->whereRaw(
                                '(6371 * acos(cos(radians(?)) * cos(radians(pickup_latitude)) * cos(radians(pickup_longitude) - radians(?)) + sin(radians(?)) * sin(radians(pickup_latitude)))) <= ?',
                                [$centerLat, $centerLng, $centerLat, $radiusKm]
                            );
                    });
            });
        }

        $timeframe = strtolower((string) ($filters['timeframe'] ?? ''));
        if ($timeframe === 'today') {
            $query->whereBetween('trip_datetime', [now()->copy()->startOfDay(), now()->copy()->endOfDay()]);
        } elseif ($timeframe === 'tomorrow') {
            $tomorrow = now()->copy()->addDay();
            $query->whereBetween('trip_datetime', [$tomorrow->copy()->startOfDay(), $tomorrow->copy()->endOfDay()]);
        } elseif ($timeframe === 'weekend') {
            $query->whereIn(\DB::raw('DAYOFWEEK(trip_datetime)'), [1, 7]);
        }

        $seatFilter = strtolower((string) ($filters['seats'] ?? ''));
        if ($seatFilter === '1') {
            $query->whereRaw(
                '(seat_limit IS NULL OR (seat_limit - (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.is_driver = 0)) = 1)'
            );
        } elseif ($seatFilter === '2plus') {
            $query->whereRaw(
                '(seat_limit IS NULL OR (seat_limit - (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.is_driver = 0)) >= 2)'
            );
        }

        if (isset($filters['fare_max']) && is_numeric($filters['fare_max'])) {
            $query->where('fare_per_person', '<=', (float) $filters['fare_max']);
        }

        if (($filters['connections'] ?? null) === '1') {
            $connectionIds = $this->acceptedConnectionIds($user);
            $query->whereIn('driver_id', $connectionIds->isNotEmpty() ? $connectionIds->all() : [-1]);
        }

        $sort = strtolower((string) ($filters['sort'] ?? 'nearest'));
        if ($sort === 'latest') {
            $query->latest('trip_datetime');
        } else {
            $query->oldest('trip_datetime');
        }

        return $query->paginate($perPage)->appends($filters);
    }

    public function exploreDestinationSuggestions(int $limit = 8): Collection
    {
        return SavedRoute::query()
            ->join('trips', 'trips.saved_route_id', '=', 'saved_routes.id')
            ->where('saved_routes.is_active', true)
            ->where('trips.visibility', 'public')
            ->where('trips.is_open_for_request', true)
            ->where('trips.status', 'scheduled')
            ->whereNull('trips.parent_trip_id')
            ->where('trips.trip_datetime', '>=', now())
            ->whereNotNull('trips.destination_name')
            ->where('trips.destination_name', '!=', '')
            ->select('trips.destination_name')
            ->distinct()
            ->orderBy('trips.destination_name')
            ->limit($limit)
            ->pluck('trips.destination_name')
            ->values();
    }

    public function getSelectableParticipants(User $user): EloquentCollection
    {
        return User::query()
            ->whereIn('id', $this->acceptedConnectionIds($user))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(User $driver, array $data): Trip
    {
        $savedRoute = $this->resolveOwnedSavedRoute($driver, (int) $data['saved_route_id']);
        $visibility = $this->resolveVisibility($data['visibility'] ?? 'private');
        $isPublic = $visibility === 'public';
        $includeDriverInSplit = $this->shouldIncludeDriverInSplit($data);
        $tripType = $this->resolveTripType($data['trip_type'] ?? null);
        $data['participant_ids'] = $this->mergePresetPassengerIds($data['participant_ids'] ?? [], $savedRoute);
        $participantIds = $this->buildParticipantIds($driver, $data['participant_ids'] ?? [], $includeDriverInSplit, $visibility);
        $seatLimit = $this->resolveSeatLimit($data, $participantIds, $driver->id, $isPublic);
        $splitCount = $this->resolveSplitCount($participantIds->count(), $isPublic, $seatLimit, $includeDriverInSplit);
        $amounts = $this->buildActiveParticipantAmounts((float) $savedRoute->default_fare, $participantIds->count(), $splitCount);
        $isOpenForRequest = $this->resolveRequestOpenState($data, $isPublic);

        return DB::transaction(function () use (
            $driver,
            $savedRoute,
            $data,
            $participantIds,
            $amounts,
            $tripType,
            $visibility,
            $seatLimit,
            $splitCount,
            $isOpenForRequest,
            $isPublic
        ): Trip {
            $status = $this->resolveStatus($data['status'] ?? null, (string) $data['trip_datetime']);
            $tripMode = $tripType === 'two_way' ? 'two_way' : 'one_way';
            $outboundDirection = $this->resolveDirection($savedRoute, (string) $data['outbound_pickup_key'], (string) $data['outbound_destination_key']);

            $trip = Trip::query()->create([
                'driver_id' => $driver->id,
                'saved_route_id' => $savedRoute->id,
                ...$outboundDirection,
                'parent_trip_id' => null,
                'trip_datetime' => $data['trip_datetime'],
                'trip_mode' => $tripMode,
                'visibility' => $visibility,
                'is_return_trip' => false,
                'status' => $status,
                'fare_total' => $savedRoute->default_fare,
                'fare_per_person' => $this->farePerPerson((float) $savedRoute->default_fare, $splitCount),
                'participant_count' => $participantIds->count(),
                'seat_limit' => $seatLimit,
                'is_open_for_request' => $isPublic ? $isOpenForRequest : false,
                'note' => $data['note'] ?? null,
                'public_note' => $isPublic ? ($data['public_note'] ?? null) : null,
            ]);

            $tripRef = sprintf('TRP-%05d', $trip->id);
            $trip->update(['trip_ref' => $tripRef]);

            $this->syncParticipantsAndPayments($trip, $driver->id, $participantIds, $amounts, $savedRoute);
            $this->notifyParticipants($trip, $driver->name, 'Trip Created', 'trip');

            if ($tripType === 'two_way') {
                $returnAmounts = $this->buildActiveParticipantAmounts((float) $savedRoute->default_fare, $participantIds->count(), $splitCount);
                $returnDirection = $this->resolveDirection($savedRoute, (string) $data['return_pickup_key'], (string) $data['return_destination_key']);

                $returnTrip = Trip::query()->create([
                    'driver_id' => $driver->id,
                    'saved_route_id' => $savedRoute->id,
                    ...$returnDirection,
                    'parent_trip_id' => $trip->id,
                    'trip_ref' => $tripRef,
                    'trip_datetime' => $data['trip_datetime'],
                    'trip_mode' => 'two_way',
                    'visibility' => $visibility,
                    'is_return_trip' => true,
                    'status' => $status,
                    'fare_total' => $savedRoute->default_fare,
                    'fare_per_person' => $this->farePerPerson((float) $savedRoute->default_fare, $splitCount),
                    'participant_count' => $participantIds->count(),
                    'seat_limit' => $seatLimit,
                    'is_open_for_request' => $isPublic ? $isOpenForRequest : false,
                    'note' => $data['note'] ?? null,
                    'public_note' => $isPublic ? ($data['public_note'] ?? null) : null,
                ]);

                $this->syncParticipantsAndPayments($returnTrip, $driver->id, $participantIds, $returnAmounts, $savedRoute);
                $this->notifyParticipants($returnTrip, $driver->name, 'Trip Created', 'trip');
            }

            return $trip->load(['savedRoute', 'participants.user', 'payments.user']);
        });
    }

    public function update(User $actor, Trip $trip, array $data): Trip
    {
        $this->ensureTripOwner($actor, $trip);

        $savedRoute = $this->resolveOwnedSavedRoute($actor, (int) $data['saved_route_id']);
        $visibility = $this->resolveVisibility($data['visibility'] ?? $trip->visibility ?? 'private');
        $isPublic = $visibility === 'public';
        $includeDriverInSplit = $this->shouldIncludeDriverInSplit($data);
        $data['participant_ids'] = $this->mergePresetPassengerIds($data['participant_ids'] ?? [], $savedRoute);
        $incomingParticipantIds = $this->buildParticipantIds($actor, $data['participant_ids'] ?? [], $includeDriverInSplit, $visibility);
        $existingParticipantIds = $isPublic
            ? $trip->participants()->pluck('user_id')
            : collect();
        $participantIds = $incomingParticipantIds->merge($existingParticipantIds)->unique()->values();
        $seatLimit = $this->resolveSeatLimit($data, $participantIds, $actor->id, $isPublic);
        $splitCount = $this->resolveSplitCount($participantIds->count(), $isPublic, $seatLimit, $includeDriverInSplit);
        $amounts = $this->buildActiveParticipantAmounts((float) $savedRoute->default_fare, $participantIds->count(), $splitCount);
        $isOpenForRequest = $this->resolveRequestOpenState($data, $isPublic, (bool) $trip->is_open_for_request);
        $previousPassengerIds = $trip->participants()->where('is_driver', false)->pluck('user_id');

        return DB::transaction(function () use ($trip, $savedRoute, $data, $participantIds, $amounts, $actor, $visibility, $seatLimit, $splitCount, $isOpenForRequest, $isPublic, $previousPassengerIds): Trip {
            $status = $this->resolveStatus($data['status'] ?? null, (string) $data['trip_datetime'], $trip->status);
            $outboundDirection = $this->resolveDirection($savedRoute, (string) $data['outbound_pickup_key'], (string) $data['outbound_destination_key']);

            $trip->update([
                'saved_route_id' => $savedRoute->id,
                ...$outboundDirection,
                'trip_datetime' => $data['trip_datetime'],
                'visibility' => $visibility,
                'status' => $status,
                'fare_total' => $savedRoute->default_fare,
                'fare_per_person' => $this->farePerPerson((float) $savedRoute->default_fare, $splitCount),
                'participant_count' => $participantIds->count(),
                'seat_limit' => $seatLimit,
                'is_open_for_request' => $isPublic ? $isOpenForRequest : false,
                'note' => $data['note'] ?? null,
                'public_note' => $isPublic ? ($data['public_note'] ?? null) : null,
            ]);

            if ($trip->returnTrip) {
                $returnDirection = $this->resolveDirection(
                    $savedRoute,
                    (string) ($data['return_pickup_key'] ?? 'point_b'),
                    (string) ($data['return_destination_key'] ?? 'point_a')
                );

                $trip->returnTrip->update([
                    'saved_route_id' => $savedRoute->id,
                    ...$returnDirection,
                    'trip_datetime' => $data['trip_datetime'],
                    'visibility' => $visibility,
                    'status' => $status,
                    'fare_total' => $savedRoute->default_fare,
                    'fare_per_person' => $this->farePerPerson((float) $savedRoute->default_fare, $splitCount),
                    'participant_count' => $participantIds->count(),
                    'seat_limit' => $seatLimit,
                    'is_open_for_request' => $isPublic ? $isOpenForRequest : false,
                    'note' => $data['note'] ?? null,
                    'public_note' => $isPublic ? ($data['public_note'] ?? null) : null,
                ]);

                $this->syncParticipantsAndPayments($trip->returnTrip, $trip->driver_id, $participantIds, $amounts, $savedRoute);
            }

            $this->syncParticipantsAndPayments($trip, $trip->driver_id, $participantIds, $amounts, $savedRoute);

            $removedIds = $previousPassengerIds->diff($participantIds)->values();
            if ($removedIds->isNotEmpty()) {
                $label = $this->tripLabel($trip);
                $now = now();
                UserNotification::insert(
                    $removedIds->map(fn ($userId) => [
                        'user_id'      => $userId,
                        'type'         => 'trip',
                        'title'        => 'Removed from Trip',
                        'message'      => "You have been removed from the {$label}. Please contact the driver if you have any questions.",
                        'related_type' => 'system',
                        'related_id'   => null,
                        'is_read'      => false,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ])->all()
                );
            }

            $this->notifyParticipants($trip, $actor->name, 'Trip Updated', 'trip');

            return $trip->refresh()->load(['savedRoute', 'participants.user', 'payments.user']);
        });
    }

    public function delete(User $actor, Trip $trip): void
    {
        $this->ensureTripOwner($actor, $trip);

        $baseTrip = $trip->is_return_trip && $trip->parentTrip
            ? $trip->parentTrip
            : $trip;

        $baseTrip->loadMissing('participants');
        $passengerIds = $baseTrip->participants
            ->where('is_driver', false)
            ->pluck('user_id')
            ->values();
        $label = $this->tripLabel($baseTrip);

        DB::transaction(function () use ($baseTrip, $passengerIds, $label): void {
            $tripIds = Trip::query()
                ->where('id', $baseTrip->id)
                ->orWhere('parent_trip_id', $baseTrip->id)
                ->pluck('id');

            UserNotification::query()
                ->where('related_type', 'trip')
                ->whereIn('related_id', $tripIds)
                ->delete();

            Trip::query()->whereIn('id', $tripIds)->delete();

            if ($passengerIds->isNotEmpty()) {
                $now = now();
                UserNotification::insert(
                    $passengerIds->map(fn ($userId) => [
                        'user_id'      => $userId,
                        'type'         => 'trip',
                        'title'        => 'Trip Cancelled',
                        'message'      => "The {$label} has been cancelled by the driver. Please make alternative transport arrangements.",
                        'related_type' => 'system',
                        'related_id'   => null,
                        'is_read'      => false,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ])->all()
                );
            }
        });
    }

    public function ensureTripOwner(User $actor, Trip $trip): void
    {
        if ($actor->role !== 'admin' && $trip->driver_id !== $actor->id) {
            abort(403);
        }
    }

    public function ensureTripAccessible(User $actor, Trip $trip): void
    {
        if ($actor->role === 'admin' || $trip->driver_id === $actor->id) {
            return;
        }

        $isParticipant = TripParticipant::query()
            ->where('trip_id', $trip->id)
            ->where('user_id', $actor->id)
            ->exists();

        abort_unless($isParticipant, 403);
    }

    private function resolveOwnedSavedRoute(User $driver, int $savedRouteId): SavedRoute
    {
        $savedRoute = SavedRoute::query()
            ->with('passengerStops')
            ->where('id', $savedRouteId)
            ->where('user_id', $driver->id)
            ->where('is_active', true)
            ->first();

        if (! $savedRoute) {
            throw ValidationException::withMessages([
                'saved_route_id' => 'Selected saved route is invalid for your account.',
            ]);
        }

        return $savedRoute;
    }

    private function buildParticipantIds(User $driver, array $participantIds, bool $includeDriverInSplit, string $visibility = 'private'): Collection
    {
        $participantIds = collect($participantIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($participantIds->isNotEmpty()) {
            $allowedIds = $this->acceptedConnectionIds($driver)->flip();
            $invalid = $participantIds->filter(fn ($id) => ! $allowedIds->has($id));

            if ($invalid->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'participant_ids' => 'Participants must be selected from accepted connections.',
                ]);
            }
        }

        if ($includeDriverInSplit) {
            return $participantIds->prepend($driver->id)->unique()->values();
        }

        if ($participantIds->isEmpty() && $visibility !== 'public') {
            throw ValidationException::withMessages([
                'participant_ids' => 'Select at least one participant when driver is excluded from fare split.',
            ]);
        }

        return $participantIds->values();
    }

    private function acceptedConnectionIds(User $user): Collection
    {
        return Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($user): void {
                $query->where('requester_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->selectRaw(
                'CASE WHEN requester_id = ? THEN receiver_id ELSE requester_id END as connected_user_id',
                [$user->id]
            )
            ->pluck('connected_user_id')
            ->unique()
            ->values();
    }

    private function buildActiveParticipantAmounts(float $fareTotal, int $activeCount, int $splitCount): Collection
    {
        if ($activeCount <= 0) {
            return collect();
        }

        $perPerson = $this->farePerPerson($fareTotal, $splitCount);

        return collect(range(1, $activeCount))
            ->map(fn (): float => $perPerson);
    }

    private function farePerPerson(float $fareTotal, int $splitCount): float
    {
        if ($splitCount <= 0) {
            return 0;
        }

        return round($fareTotal / $splitCount, 2);
    }

    private function syncParticipantsAndPayments(
        Trip $trip,
        int $driverId,
        Collection $participantIds,
        Collection $amounts,
        ?SavedRoute $savedRoute = null
    ): void {
        $presetStopsByUser = $savedRoute
            ? $savedRoute->passengerStops->where('is_active', true)->keyBy('user_id')
            : collect();

        TripParticipant::query()->where('trip_id', $trip->id)->delete();
        TripPayment::query()->where('trip_id', $trip->id)->delete();
        if ($trip->visibility === 'private') {
            TripPassengerRoutePoint::query()
                ->where('trip_id', $trip->id)
                ->whereNull('trip_join_request_id')
                ->update([
                    'trip_participant_id' => null,
                    'status' => 'removed',
                ]);
        }

        $now = now();
        $participantRows = [];
        $paymentRows = [];

        foreach ($participantIds as $index => $userId) {
            $presetStop = $presetStopsByUser->get($userId);
            $baseFareAmount = (float) $amounts->get($index, 0);
            $extraFareAmount = $presetStop?->extra_fee_amount !== null
                ? (float) $presetStop->extra_fee_amount
                : 0.0;
            $fareAmount = round($baseFareAmount + $extraFareAmount, 2);

            $participantRows[] = [
                'trip_id' => $trip->id,
                'user_id' => $userId,
                'is_driver' => $userId === $driverId,
                'fare_amount' => $fareAmount,
                'attendance_status' => 'joined',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $paymentRows[] = [
                'trip_id' => $trip->id,
                'user_id' => $userId,
                'amount_due' => $fareAmount,
                'payment_status' => 'unpaid',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        TripParticipant::insert($participantRows);
        TripPayment::insert($paymentRows);

        if ($trip->visibility === 'private') {
            $insertedParticipants = TripParticipant::query()
                ->where('trip_id', $trip->id)
                ->whereIn('user_id', $participantIds->all())
                ->where('is_driver', false)
                ->get()
                ->keyBy('user_id');

            foreach ($participantIds as $userId) {
                if ((int) $userId === $driverId) {
                    continue;
                }
                $presetStop = $presetStopsByUser->get($userId);
                $participant = $insertedParticipants->get($userId);

                TripPassengerRoutePoint::query()->updateOrCreate(
                    [
                        'trip_id' => $trip->id,
                        'user_id' => $userId,
                        'trip_join_request_id' => null,
                    ],
                    [
                        'trip_participant_id' => $participant?->id,
                        ...$this->routePointPayloadFromPreset($trip, $savedRoute, $presetStop),
                        'requested_pickup_time' => $trip->trip_datetime,
                        'route_fit_score' => 100,
                        'route_fit_label' => $presetStop ? 'Saved route preset passenger stop' : 'Private trip default pickup and destination',
                        'pickup_distance_km' => 0,
                        'dropoff_distance_km' => 0,
                        'detour_distance_km' => null,
                        'detour_duration_minutes' => null,
                        'extra_fee_amount' => $presetStop?->extra_fee_amount,
                        'status' => 'accepted',
                    ]
                );
            }
        }
    }

    private function mergePresetPassengerIds(array $participantIds, SavedRoute $savedRoute): array
    {
        return collect($participantIds)
            ->merge($savedRoute->passengerStops->where('is_active', true)->pluck('user_id'))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function routePointPayloadFromPreset(Trip $trip, ?SavedRoute $savedRoute, $presetStop): array
    {
        if (! $presetStop) {
            return [
                'pickup_name' => $trip->pickup_name,
                'pickup_latitude' => $trip->pickup_latitude,
                'pickup_longitude' => $trip->pickup_longitude,
                'dropoff_name' => $trip->destination_name,
                'dropoff_latitude' => $trip->destination_latitude,
                'dropoff_longitude' => $trip->destination_longitude,
                'uses_default_pickup' => true,
                'uses_default_dropoff' => true,
            ];
        }

        $isReversed = $savedRoute
            && (string) $trip->pickup_name === (string) $savedRoute->point_b_name
            && (string) $trip->destination_name === (string) $savedRoute->point_a_name;

        if ($isReversed) {
            return [
                'pickup_name' => $presetStop->dropoff_name ?: $trip->pickup_name,
                'pickup_latitude' => $presetStop->dropoff_latitude ?: $trip->pickup_latitude,
                'pickup_longitude' => $presetStop->dropoff_longitude ?: $trip->pickup_longitude,
                'dropoff_name' => $presetStop->pickup_name ?: $trip->destination_name,
                'dropoff_latitude' => $presetStop->pickup_latitude ?: $trip->destination_latitude,
                'dropoff_longitude' => $presetStop->pickup_longitude ?: $trip->destination_longitude,
                'uses_default_pickup' => false,
                'uses_default_dropoff' => false,
            ];
        }

        return [
            'pickup_name' => $presetStop->pickup_name ?: $trip->pickup_name,
            'pickup_latitude' => $presetStop->pickup_latitude ?: $trip->pickup_latitude,
            'pickup_longitude' => $presetStop->pickup_longitude ?: $trip->pickup_longitude,
            'dropoff_name' => $presetStop->dropoff_name ?: $trip->destination_name,
            'dropoff_latitude' => $presetStop->dropoff_latitude ?: $trip->destination_latitude,
            'dropoff_longitude' => $presetStop->dropoff_longitude ?: $trip->destination_longitude,
            'uses_default_pickup' => false,
            'uses_default_dropoff' => false,
        ];
    }

    private function shortenAddress(string $address): string
    {
        $first = trim(explode(',', $address)[0]);
        $source = mb_strlen($first) >= 4 ? $first : $address;
        return mb_strimwidth($source, 0, 28, '…');
    }

    private function tripLabel(Trip $trip): string
    {
        if ($trip->pickup_name && $trip->destination_name) {
            $pickup      = $this->shortenAddress($trip->pickup_name);
            $destination = $this->shortenAddress($trip->destination_name);
            $date        = $trip->trip_datetime?->format('d M Y') ?? '';
            return $date ? "{$pickup} → {$destination} on {$date}" : "{$pickup} → {$destination}";
        }

        return 'Trip #' . ($trip->trip_ref ?? $trip->id);
    }

    private function notifyParticipants(Trip $trip, string $actorName, string $title, string $type): void
    {
        $trip->loadMissing('participants');
        $label = $this->tripLabel($trip);

        $now = now();
        $rows = $trip->participants
            ->filter(fn ($p) => $p->user_id !== $trip->driver_id)
            ->map(fn ($p) => [
                'user_id'      => $p->user_id,
                'type'         => $type,
                'title'        => $title,
                'message'      => match ($title) {
                    'Trip Created' => "You have been added to the {$label}. Check your trip details and upcoming schedule.",
                    'Trip Updated' => "The {$label} has been updated by {$actorName}. Please review the latest trip details.",
                    default        => "{$actorName} updated the {$label}.",
                },
                'related_type' => 'trip',
                'related_id'   => $trip->id,
                'is_read'      => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ])
            ->values()
            ->all();

        if ($rows) {
            UserNotification::insert($rows);
        }
    }

    public function syncLifecycleStatuses(): void
    {
        if (self::$lifecycleSynced) {
            return;
        }

        self::$lifecycleSynced = true;
        $now = now();

        Trip::query()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('trip_datetime', '>', $now)
            ->where('status', '!=', 'scheduled')
            ->update(['status' => 'scheduled']);

        Trip::query()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('trip_datetime', '<=', $now)
            ->where('status', '!=', 'recorded')
            ->update(['status' => 'recorded']);
    }

    private function resolveStatus(?string $inputStatus, string $tripDateTime, ?string $currentStatus = null): string
    {
        $status = strtolower(trim((string) $inputStatus));

        if ($status === 'confirmed') {
            return 'scheduled';
        }

        if ($status === 'completed') {
            return 'recorded';
        }

        if (in_array($status, ['draft', 'scheduled', 'recorded', 'cancelled'], true)) {
            return $status;
        }

        if ($currentStatus === 'cancelled') {
            return 'cancelled';
        }

        return now()->lt(\Illuminate\Support\Carbon::parse($tripDateTime))
            ? 'scheduled'
            : 'recorded';
    }

    private function shouldIncludeDriverInSplit(array $data): bool
    {
        return (string) ($data['include_driver_in_split'] ?? '1') === '1';
    }

    private function resolveTripType(?string $tripType): string
    {
        return strtolower(trim((string) $tripType)) === 'two_way' ? 'two_way' : 'one_way';
    }

    private function resolveVisibility(?string $visibility): string
    {
        return strtolower(trim((string) $visibility)) === 'public' ? 'public' : 'private';
    }

    private function resolveSeatLimit(array $data, Collection $participantIds, int $driverId, bool $isPublic): ?int
    {
        if (! $isPublic) {
            return null;
        }

        $seatLimit = isset($data['seat_limit']) ? (int) $data['seat_limit'] : null;
        if (! $seatLimit || $seatLimit < 1) {
            throw ValidationException::withMessages([
                'seat_limit' => 'Seat limit is required for public trips.',
            ]);
        }

        $currentPassengerCount = $participantIds
            ->filter(fn ($userId) => (int) $userId !== $driverId)
            ->count();

        if ($seatLimit < $currentPassengerCount) {
            throw ValidationException::withMessages([
                'seat_limit' => 'Seat limit cannot be less than current passenger count.',
            ]);
        }

        return $seatLimit;
    }

    private function resolveSplitCount(int $participantCount, bool $isPublic, ?int $seatLimit, bool $includeDriverInSplit): int
    {
        if ($isPublic && $seatLimit && $seatLimit > 0) {
            return $seatLimit + ($includeDriverInSplit ? 1 : 0);
        }

        return max(1, $participantCount);
    }

    private function resolveRequestOpenState(array $data, bool $isPublic, ?bool $currentState = null): bool
    {
        if (! $isPublic) {
            return false;
        }

        if ($currentState !== null) {
            return $currentState;
        }

        return true;
    }

    private function resolveDirection(SavedRoute $savedRoute, string $pickupKey, string $destinationKey): array
    {
        return [
            'pickup_name' => $this->directionValue($savedRoute, $pickupKey, 'name'),
            'pickup_latitude' => $this->directionValue($savedRoute, $pickupKey, 'latitude'),
            'pickup_longitude' => $this->directionValue($savedRoute, $pickupKey, 'longitude'),
            'destination_name' => $this->directionValue($savedRoute, $destinationKey, 'name'),
            'destination_latitude' => $this->directionValue($savedRoute, $destinationKey, 'latitude'),
            'destination_longitude' => $this->directionValue($savedRoute, $destinationKey, 'longitude'),
        ];
    }

    private function directionValue(SavedRoute $savedRoute, string $pointKey, string $field): string|float|null
    {
        $pointKey = strtolower(trim($pointKey)) === 'point_b' ? 'point_b' : 'point_a';
        $attribute = "{$pointKey}_{$field}";

        return $savedRoute->{$attribute};
    }
}
