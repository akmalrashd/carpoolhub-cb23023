<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\SavedRoute;
use App\Models\Trip;
use App\Models\TripCancellationLog;
use App\Models\TripParticipant;
use App\Models\TripPassengerRoutePoint;
use App\Models\TripPayment;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Concerns\FormatsTripLabel;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripService
{
    use FormatsTripLabel;

    private static bool $lifecycleSynced = false;

    public function paginateForUser(User $user, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $this->syncLifecycleStatuses();

        // Eight of these relations load users, and users carries the two
        // multi-megabyte base64 image columns. Without withoutHeavyMedia() one
        // page of trips materialises the same driver/passenger rows — blobs and
        // all — once per relation. The trips views never render either column,
        // so scoping them out is invisible. Matches PaymentService and
        // DashboardController, which already do this.
        $withoutHeavyMedia = fn ($relationQuery) => $relationQuery->withoutHeavyMedia();

        $query = $this->baseUserTripsQuery($user)
            ->with([
                'savedRoute',
                'driver' => $withoutHeavyMedia,
                'participants.user' => $withoutHeavyMedia,
                'joinRequests.user' => $withoutHeavyMedia,
                'joinRequests.routePoint',
                'payments.user' => $withoutHeavyMedia,
                'returnTrip.savedRoute',
                'returnTrip.participants.user' => $withoutHeavyMedia,
                'returnTrip.payments.user' => $withoutHeavyMedia,
                'passengerRoutePoints.user' => $withoutHeavyMedia,
                'returnTrip.passengerRoutePoints.user' => $withoutHeavyMedia,
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

        return $query->latest('trip_datetime')->orderByDesc('id')->paginate($perPage);
    }

    public function statusCountsForUser(User $user, array $filters = []): array
    {
        $query = $this->baseUserTripsQuery($user);

        $this->applyTripIndexFilters($query, $filters);

        // One grouped query replaces five separate COUNTs over the same filtered
        // set (each of which re-ran the whereHas participants subquery). Summing
        // every returned status — not just the ones named below — keeps 'all'
        // identical to the old unconditional count() even if a status appears
        // that no tab covers.
        $byStatus = (clone $query)
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $sum = fn (array $statuses): int => (int) array_sum(
            array_map(fn (string $status): int => (int) ($byStatus[$status] ?? 0), $statuses)
        );

        return [
            'all' => (int) $byStatus->sum(),
            'upcoming' => $sum(['scheduled', 'confirmed']),
            'completed' => $sum(['recorded', 'completed']),
            'draft' => $sum(['draft']),
            'cancelled' => $sum(['cancelled']),
        ];
    }

    private function baseUserTripsQuery(User $user)
    {
        $query = Trip::query()
            ->whereNull('parent_trip_id');

        if ($user->role !== 'admin') {
            $query->where(function ($builder) use ($user): void {
                $builder->where('driver_id', $user->id)
                    ->orWhereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $user->id))
                    // A pending join request has no participant row yet (that's
                    // only created on approval), so without this a trip the user
                    // just requested to join stays invisible here until approved.
                    ->orWhereHas('joinRequests', fn ($joinRequestQuery) => $joinRequestQuery->where('user_id', $user->id)->where('status', 'pending'));
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
            ->where('trip_datetime', '>=', Trip::now());

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
            $query->whereBetween('trip_datetime', [Trip::now()->copy()->startOfDay(), Trip::now()->copy()->endOfDay()]);
        } elseif ($timeframe === 'tomorrow') {
            $tomorrow = Trip::now()->copy()->addDay();
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
            $connectionIds = Connection::acceptedUserIdsFor($user);
            $query->whereIn('driver_id', $connectionIds->isNotEmpty() ? $connectionIds->all() : [-1]);
        }

        // id tiebreaker makes paging deterministic when trip_datetime ties, and
        // lets the composite index serve the sort without a filesort. Direction
        // matches the primary sort so the index is read straight (not mixed).
        $sort = strtolower((string) ($filters['sort'] ?? 'nearest'));
        if ($sort === 'latest') {
            $query->latest('trip_datetime')->orderByDesc('id');
        } else {
            $query->oldest('trip_datetime')->orderBy('id');
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
            ->where('trips.trip_datetime', '>=', Trip::now())
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
            ->whereIn('id', Connection::acceptedUserIdsFor($user))
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
            $this->notifyDriver($trip, $driver->id, 'Trip Created', "You've created the {$this->tripLabel($trip)}.");

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

    /**
     * Fields worth telling recipients about when they change — deliberately
     * excludes derived/internal columns like status (recomputed from
     * trip_datetime, not something the actor directly chose) and the fare/
     * participant-count fields (already covered by the separate "Removed
     * from Trip" notification when someone actually drops off the trip).
     *
     * @var array<string, string>
     */
    private const TRIP_CHANGE_FIELD_LABELS = [
        'trip_datetime' => 'departure time',
        'pickup_name' => 'pickup location',
        'destination_name' => 'destination',
        'visibility' => 'visibility',
        'seat_limit' => 'seat limit',
        'note' => 'note',
        'public_note' => 'public note',
    ];

    public function update(User $actor, Trip $trip, array $data): Trip
    {
        $this->ensureTripOwner($actor, $trip);

        $beforeChange = $trip->only(array_keys(self::TRIP_CHANGE_FIELD_LABELS));

        // The trip's OWNER, not whoever is submitting the edit — ensureTripOwner()
        // above allows admin to edit any driver's trip, but every one of these
        // driver-scoped lookups (saved route ownership, accepted-connections
        // check for participant_ids, driver's own seat in the split) must stay
        // scoped to the actual driver. Using $actor here meant an admin editing
        // someone else's trip would see an empty saved-route dropdown (routes
        // are scoped by owner), and — worse — "include driver in split" would
        // silently add the ADMIN as a fare-splitting participant instead of the
        // real driver.
        $driver = $trip->driver;

        $savedRoute = $this->resolveOwnedSavedRoute($driver, (int) $data['saved_route_id']);
        $visibility = $this->resolveVisibility($data['visibility'] ?? $trip->visibility ?? 'private');
        $isPublic = $visibility === 'public';
        $includeDriverInSplit = $this->shouldIncludeDriverInSplit($data);
        $data['participant_ids'] = $this->mergePresetPassengerIds($data['participant_ids'] ?? [], $savedRoute);
        $incomingParticipantIds = $this->buildParticipantIds($driver, $data['participant_ids'] ?? [], $includeDriverInSplit, $visibility);
        $existingParticipantIds = $isPublic
            ? $trip->participants()->pluck('user_id')
            : collect();
        $participantIds = $incomingParticipantIds->merge($existingParticipantIds)->unique()->values();
        $seatLimit = $this->resolveSeatLimit($data, $participantIds, $driver->id, $isPublic);
        $splitCount = $this->resolveSplitCount($participantIds->count(), $isPublic, $seatLimit, $includeDriverInSplit);
        $amounts = $this->buildActiveParticipantAmounts((float) $savedRoute->default_fare, $participantIds->count(), $splitCount);
        $isOpenForRequest = $this->resolveRequestOpenState($data, $isPublic, (bool) $trip->is_open_for_request);
        $previousPassengerIds = $trip->participants()->where('is_driver', false)->pluck('user_id');

        return DB::transaction(function () use ($trip, $savedRoute, $data, $participantIds, $amounts, $actor, $visibility, $seatLimit, $splitCount, $isOpenForRequest, $isPublic, $previousPassengerIds, $beforeChange): Trip {
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

            $changeSummary = $this->summarizeTripChanges($beforeChange, $trip->only(array_keys(self::TRIP_CHANGE_FIELD_LABELS)));

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
                // ::create() per row, not insert() — see notifyParticipants() docblock.
                foreach ($removedIds as $userId) {
                    UserNotification::query()->create([
                        'user_id' => $userId,
                        'type' => 'trip',
                        'title' => 'Removed from Trip',
                        'message' => "You have been removed from the {$label}. Please contact the driver if you have any questions.",
                        'related_type' => 'system',
                        'related_id' => null,
                        'is_read' => false,
                    ]);
                }
            }

            $this->notifyParticipants($trip, $actor->name, 'Trip Updated', 'trip', $changeSummary);

            // notifyParticipants() deliberately excludes the driver — correct
            // when the driver is the one editing, but when admin makes the
            // edit the driver is a bystander to their own trip changing and
            // needs telling same as any passenger would.
            if ($actor->id !== $trip->driver_id) {
                $this->notifyDriver(
                    $trip,
                    $trip->driver_id,
                    'Trip Updated',
                    "An admin ({$actor->name}) updated your {$this->tripLabel($trip)}. {$changeSummary}"
                );
            }

            return $trip->refresh()->load(['savedRoute', 'participants.user', 'payments.user']);
        });
    }

    public function delete(User $actor, Trip $trip, ?string $reason = null): void
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

        $driverId = $baseTrip->driver_id;
        // "by the driver" was hardcoded here regardless of who actually
        // cancelled — wrong and misleading once admin (who shares this same
        // delete() path via ensureTripOwner()) is the one acting.
        $isAdminActing = $actor->id !== $driverId;
        $cancelledByText = $isAdminActing ? "an admin ({$actor->name})" : 'the driver';

        DB::transaction(function () use ($baseTrip, $passengerIds, $label, $actor, $reason, $driverId, $isAdminActing, $cancelledByText): void {
            $tripIds = Trip::query()
                ->where('id', $baseTrip->id)
                ->orWhere('parent_trip_id', $baseTrip->id)
                ->pluck('id');

            $this->logTripCancellation($tripIds, $actor, $reason);

            UserNotification::query()
                ->where('related_type', 'trip')
                ->whereIn('related_id', $tripIds)
                ->delete();

            Trip::query()->whereIn('id', $tripIds)->delete();

            if ($passengerIds->isNotEmpty()) {
                // ::create() per row, not insert() — see notifyParticipants() docblock.
                foreach ($passengerIds as $userId) {
                    UserNotification::query()->create([
                        'user_id' => $userId,
                        'type' => 'trip',
                        'title' => 'Trip Cancelled',
                        'message' => "The {$label} has been cancelled by {$cancelledByText}. Please make alternative transport arrangements.",
                        'related_type' => 'system',
                        'related_id' => null,
                        'is_read' => false,
                    ]);
                }
            }

            // The trip row is gone by this point, so the driver can't be
            // notified via notifyDriver()'s usual related_type:'trip' link —
            // 'system' (same as the passenger notice above) is the only
            // sensible target once there's nothing left to link back to.
            if ($isAdminActing) {
                UserNotification::query()->create([
                    'user_id' => $driverId,
                    'type' => 'trip',
                    'title' => 'Trip Cancelled',
                    'message' => "An admin ({$actor->name}) cancelled your {$label}."
                        .($reason ? " Reason: {$reason}" : ''),
                    'related_type' => 'system',
                    'related_id' => null,
                    'is_read' => false,
                ]);
            }
        });
    }

    /**
     * For the admin Audit Log's "Trip Cancellations" tab — browses
     * trip_cancellation_logs, the trail logTripCancellation() writes.
     */
    public function paginateCancellationLogs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = TripCancellationLog::query()
            ->with(['driver:id,name', 'canceller:id,name'])
            ->latest('created_at');

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('reason', 'like', "%{$q}%")
                    ->orWhereHas('driver', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('canceller', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse((string) $filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse((string) $filters['date_to'])->endOfDay());
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Snapshots every trip in $tripIds (plus its participants/payments)
     * before delete() hard-deletes them — once that runs, cascadeOnDelete()
     * on trip_participants/trip_payments takes the rest with it and the trip
     * is unrecoverable (there's no soft-delete anywhere in this app). A
     * dispute like "the driver cancelled 10 minutes before departure" needs
     * something to check against; this is that something.
     */
    private function logTripCancellation(\Illuminate\Support\Collection $tripIds, User $actor, ?string $reason): void
    {
        $trips = Trip::query()
            ->whereIn('id', $tripIds)
            ->with(['participants', 'payments'])
            ->get();

        foreach ($trips as $trip) {
            TripCancellationLog::create([
                'trip_id' => $trip->id,
                'driver_id' => $trip->driver_id,
                'cancelled_by' => $actor->id,
                'cancelled_by_role' => $actor->role,
                'reason' => $reason,
                'trip_datetime' => $trip->trip_datetime,
                'trip_snapshot' => $trip->only([
                    'id', 'driver_id', 'saved_route_id', 'parent_trip_id',
                    'pickup_name', 'pickup_latitude', 'pickup_longitude',
                    'destination_name', 'destination_latitude', 'destination_longitude',
                    'trip_datetime', 'trip_mode', 'is_return_trip', 'visibility', 'status',
                    'fare_total', 'fare_per_person', 'participant_count', 'seat_limit',
                    'is_open_for_request', 'note', 'public_note', 'created_at',
                ]),
                'participants_snapshot' => $trip->participants->map->only([
                    'id', 'user_id', 'is_driver', 'fare_amount',
                    'attendance_status', 'joined_at', 'cancelled_at',
                ])->all(),
                'payments_snapshot' => $trip->payments->map->only([
                    'id', 'user_id', 'amount_due', 'payment_status',
                    'marked_paid_at', 'confirmed_by', 'confirmed_at', 'payment_method', 'remarks',
                ])->all(),
                'created_at' => now(),
            ]);
        }
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
            $allowedIds = Connection::acceptedUserIdsFor($driver)->flip();
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

        // Payments are rebuilt wholesale just below. Snapshot what each
        // passenger has already settled BEFORE the delete: without this, editing
        // a trip — even only its note or departure time — reset every payment
        // row to 'unpaid' and discarded marked_paid_at / confirmed_by /
        // confirmed_at / payment_method / remarks, silently destroying the
        // record that a passenger had paid and a driver had confirmed it.
        // On trip creation this is empty, so that path is byte-for-byte the same.
        $settledPayments = TripPayment::query()
            ->where('trip_id', $trip->id)
            ->get()
            ->keyBy(fn (TripPayment $payment): int => (int) $payment->user_id);

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

            // Carry the settlement state forward for a passenger who already had
            // a payment row on this trip. amount_due still takes the newly
            // computed fare, which is the point of the edit.
            $settled = $settledPayments->get((int) $userId);

            $paymentRows[] = [
                'trip_id' => $trip->id,
                'user_id' => $userId,
                'amount_due' => $fareAmount,
                'payment_status' => $settled->payment_status ?? 'unpaid',
                'marked_paid_at' => $settled->marked_paid_at ?? null,
                'confirmed_by' => $settled->confirmed_by ?? null,
                'confirmed_at' => $settled->confirmed_at ?? null,
                'payment_method' => $settled->payment_method ?? null,
                'remarks' => $settled->remarks ?? null,
                'created_at' => $settled->created_at ?? $now,
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

    private function tripLabel(Trip $trip): string
    {
        return $this->formatTripLabel($trip);
    }

    /**
     * Uses ::create() per row (not a bulk insert) on purpose — UserNotificationObserver
     * only fires on the Eloquent "created" event, which is what actually sends the
     * push/Telegram notification. A bulk insert() skips that silently, leaving only
     * the in-app row behind with no delivery at all.
     */
    private function notifyParticipants(Trip $trip, string $actorName, string $title, string $type, ?string $changeSummary = null): void
    {
        $trip->loadMissing('participants');
        $label = $this->tripLabel($trip);

        $recipientIds = $trip->participants
            ->filter(fn ($p) => $p->user_id !== $trip->driver_id)
            ->pluck('user_id');

        foreach ($recipientIds as $userId) {
            UserNotification::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => match ($title) {
                    'Trip Created' => "You have been added to the {$label}. Check your trip details and upcoming schedule.",
                    'Trip Updated' => "{$actorName} updated the {$label}. {$changeSummary}",
                    default => "{$actorName} updated the {$label}.",
                },
                'related_type' => 'trip',
                'related_id' => $trip->id,
                'is_read' => false,
            ]);
        }
    }

    /**
     * Builds a human-readable "X changed from A to B" list for a trip edit —
     * reused for both the participants' notification and the driver's (when
     * admin is the one editing). Compares TRIP_CHANGE_FIELD_LABELS fields
     * only; see that constant's docblock for why the rest are excluded.
     */
    private function summarizeTripChanges(array $before, array $after): string
    {
        $changes = [];

        foreach (self::TRIP_CHANGE_FIELD_LABELS as $field => $label) {
            $oldValue = $before[$field] ?? null;
            $newValue = $after[$field] ?? null;

            if ($field === 'trip_datetime') {
                $oldValue = $oldValue instanceof Carbon ? $oldValue->format('d M Y, h:ia') : $oldValue;
                $newValue = $newValue instanceof Carbon ? $newValue->format('d M Y, h:ia') : $newValue;
            }

            $oldValue = $oldValue === '' ? null : $oldValue;
            $newValue = $newValue === '' ? null : $newValue;

            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            $changes[] = match (true) {
                $oldValue === null => ucfirst($label)." set to \"{$newValue}\"",
                $newValue === null => ucfirst($label).' removed',
                default => ucfirst($label)." changed from \"{$oldValue}\" to \"{$newValue}\"",
            };
        }

        return $changes === [] ? 'Other trip details were adjusted.' : implode('; ', $changes).'.';
    }

    private function notifyDriver(Trip $trip, int $driverId, string $title, string $message): void
    {
        UserNotification::query()->create([
            'user_id' => $driverId,
            'type' => 'trip',
            'title' => $title,
            'message' => $message,
            'related_type' => 'trip',
            'related_id' => $trip->id,
            'is_read' => false,
        ]);
    }

    public function syncLifecycleStatuses(): void
    {
        if (self::$lifecycleSynced) {
            return;
        }

        self::$lifecycleSynced = true;
        $now = Trip::now();

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
