<?php

namespace App\Services\Ai;

use App\Models\Connection;
use App\Models\SavedRoute;
use App\Models\Trip;
use App\Models\TripJoinRequest;
use App\Models\TripParticipant;
use App\Models\TripPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FeatureEngineeringService
{
    public function passengerRiskFeatures(User $user): array
    {
        $joinRequestBase = TripJoinRequest::query()->where('user_id', $user->id);
        $participantBase = TripParticipant::query()->where('user_id', $user->id)->where('is_driver', false);
        $paymentBase = TripPayment::query()->where('user_id', $user->id);

        $paymentDelays = $paymentBase
            ->whereNotNull('marked_paid_at')
            ->whereHas('trip', fn ($query) => $query->whereNotNull('trip_datetime'))
            ->with('trip:id,trip_datetime')
            ->get()
            ->map(function (TripPayment $payment): ?float {
                if (! $payment->trip?->trip_datetime || ! $payment->marked_paid_at) {
                    return null;
                }

                return (float) Carbon::parse($payment->trip->trip_datetime)
                    ->diffInHours(Carbon::parse($payment->marked_paid_at), false);
            })
            ->filter(fn ($hours) => $hours !== null);

        return [
            'join_request_count' => (int) (clone $joinRequestBase)->count(),
            'approved_request_count' => (int) (clone $joinRequestBase)->where('status', 'approved')->count(),
            'rejected_request_count' => (int) (clone $joinRequestBase)->where('status', 'rejected')->count(),
            'cancelled_request_count' => (int) (clone $joinRequestBase)->where('status', 'cancelled')->count(),
            'attendance_absent_count' => (int) (clone $participantBase)->where('attendance_status', 'absent')->count(),
            'outstanding_amount' => round((float) (clone $paymentBase)->whereIn('payment_status', ['unpaid', 'pending_confirmation'])->sum('amount_due'), 2),
            'overdue_case_count' => (int) (clone $paymentBase)
                ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
                ->whereHas('trip', fn ($query) => $query->where('trip_datetime', '<', now()))
                ->count(),
            'avg_payment_delay_hours' => round((float) ($paymentDelays->avg() ?? 0), 2),
        ];
    }

    /**
     * User-invariant inputs for recommendation scoring: the user's active saved
     * routes and their preferred travel hour. Both depend only on the user, not
     * the trip, so ranking a page of trips computes this ONCE instead of per
     * trip. preferred_hour is null when the user has no past joined trips (the
     * old code's "empty" branch); it may legitimately be 0 (midnight), so
     * callers must check `=== null`, not falsiness.
     *
     * Passing $trips additionally precomputes the three per-trip lookups that
     * connectionScore()/historyScore()/seatScore() would otherwise run one
     * query each for: ranking a page of N trips fired 3N extra queries (12
     * trips = 36, on every explore search/filter). Omitting $trips keeps the
     * old one-query-per-trip behaviour for scoreTripForUser() called alone.
     *
     * @return array{
     *     saved_routes: Collection<int, SavedRoute>,
     *     preferred_hour: int|null,
     *     connected_driver_ids: Collection<int, int>|null,
     *     joined_driver_ids: Collection<int, int>|null,
     *     taken_seats_by_trip: Collection<int, int>|null,
     * }
     */
    public function recommendationContext(User $user, ?Collection $trips = null): array
    {
        $savedRoutes = SavedRoute::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $pastHours = TripParticipant::query()
            ->where('user_id', $user->id)
            ->where('is_driver', false)
            ->whereHas('trip', fn ($query) => $query->whereNotNull('trip_datetime'))
            ->with('trip:id,trip_datetime')
            ->get()
            ->map(fn (TripParticipant $participant) => (int) $participant->trip->trip_datetime->format('H'));

        $context = [
            'saved_routes' => $savedRoutes,
            'preferred_hour' => $pastHours->isEmpty() ? null : (int) round($pastHours->avg()),
            'connected_driver_ids' => null,
            'joined_driver_ids' => null,
            'taken_seats_by_trip' => null,
        ];

        if ($trips === null || $trips->isEmpty()) {
            return $context;
        }

        $driverIds = $trips->pluck('driver_id')->filter()->unique()->values();
        $tripIds = $trips->pluck('id')->values();

        // Same accepted-connection set connectionScore() checked one driver at a
        // time; reused here so "connected to this driver" needs no per-trip query.
        $context['connected_driver_ids'] = Connection::acceptedUserIdsFor($user)
            ->intersect($driverIds)
            ->values();

        $context['joined_driver_ids'] = TripParticipant::query()
            ->join('trips', 'trips.id', '=', 'trip_participants.trip_id')
            ->where('trip_participants.user_id', $user->id)
            ->where('trip_participants.is_driver', false)
            ->whereIn('trips.driver_id', $driverIds)
            ->distinct()
            ->pluck('trips.driver_id');

        $context['taken_seats_by_trip'] = TripParticipant::query()
            ->whereIn('trip_id', $tripIds)
            ->where('is_driver', false)
            ->groupBy('trip_id')
            ->selectRaw('trip_id, COUNT(*) as taken')
            ->pluck('taken', 'trip_id');

        return $context;
    }

    /**
     * Batched equivalent of passengerRiskFeatures() for many users at once, so
     * scoring a trip's join requests runs a handful of grouped queries instead
     * of ~9 per passenger.
     *
     * IMPORTANT — this reproduces a quirk of passengerRiskFeatures() exactly, on
     * purpose. There, $paymentBase is mutated in place (not cloned) by the
     * $paymentDelays chain, so the later outstanding_amount / overdue_case_count
     * queries silently inherit `marked_paid_at IS NOT NULL` and a trip_datetime
     * predicate. To return identical numbers, both aggregates below carry those
     * same predicates. avg_payment_delay_hours stays computed in PHP with Carbon
     * (never SQL AVG) so float/rounding is bit-for-bit identical.
     *
     * @param  array<int>  $ids
     * @return array<int, array<string, mixed>>
     */
    public function passengerRiskFeaturesForUsers(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        $now = now();

        $joinRequests = TripJoinRequest::query()
            ->whereIn('user_id', $ids)
            ->groupBy('user_id')
            ->selectRaw("user_id,
                COUNT(*) as total,
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected,
                SUM(status = 'cancelled') as cancelled")
            ->get()->keyBy('user_id');

        $absent = TripParticipant::query()
            ->whereIn('user_id', $ids)->where('is_driver', false)->where('attendance_status', 'absent')
            ->groupBy('user_id')->selectRaw('user_id, COUNT(*) as cnt')
            ->get()->keyBy('user_id');

        // Leaked predicates replicated: marked_paid_at NOT NULL + trip_datetime NOT NULL.
        $outstanding = TripPayment::query()
            ->whereIn('user_id', $ids)
            ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
            ->whereNotNull('marked_paid_at')
            ->whereHas('trip', fn ($query) => $query->whereNotNull('trip_datetime'))
            ->groupBy('user_id')->selectRaw('user_id, SUM(amount_due) as amt')
            ->get()->keyBy('user_id');

        // Leaked marked_paid_at NOT NULL; trip_datetime < now already implies NOT NULL.
        $overdue = TripPayment::query()
            ->whereIn('user_id', $ids)
            ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
            ->whereNotNull('marked_paid_at')
            ->whereHas('trip', fn ($query) => $query->where('trip_datetime', '<', $now))
            ->groupBy('user_id')->selectRaw('user_id, COUNT(*) as cnt')
            ->get()->keyBy('user_id');

        // Delay rows for the PHP avg — same filter as passengerRiskFeatures' $paymentDelays.
        $delayRows = TripPayment::query()
            ->whereIn('user_id', $ids)
            ->whereNotNull('marked_paid_at')
            ->whereHas('trip', fn ($query) => $query->whereNotNull('trip_datetime'))
            ->with('trip:id,trip_datetime')
            ->get()->groupBy('user_id');

        $out = [];
        foreach ($ids as $id) {
            $jr = $joinRequests->get($id);

            $delays = collect($delayRows->get($id) ?? [])
                ->map(function (TripPayment $payment): ?float {
                    if (! $payment->trip?->trip_datetime || ! $payment->marked_paid_at) {
                        return null;
                    }

                    return (float) Carbon::parse($payment->trip->trip_datetime)
                        ->diffInHours(Carbon::parse($payment->marked_paid_at), false);
                })
                ->filter(fn ($hours) => $hours !== null);

            $out[$id] = [
                'join_request_count' => (int) ($jr->total ?? 0),
                'approved_request_count' => (int) ($jr->approved ?? 0),
                'rejected_request_count' => (int) ($jr->rejected ?? 0),
                'cancelled_request_count' => (int) ($jr->cancelled ?? 0),
                'attendance_absent_count' => (int) ($absent->get($id)->cnt ?? 0),
                'outstanding_amount' => round((float) ($outstanding->get($id)->amt ?? 0), 2),
                'overdue_case_count' => (int) ($overdue->get($id)->cnt ?? 0),
                'avg_payment_delay_hours' => round((float) ($delays->avg() ?? 0), 2),
            ];
        }

        return $out;
    }

    public function recommendationFeatures(User $user, Trip $trip, ?array $context = null): array
    {
        $context ??= $this->recommendationContext($user);
        $savedRoutes = $context['saved_routes'];

        $timePreferenceScore = $this->timePreferenceScore($context['preferred_hour'], $trip);
        $routeScore = $this->routeAlignmentScore($savedRoutes, $trip);
        $connectionScore = $this->connectionScore($user, $trip, $context['connected_driver_ids'] ?? null);
        $historyScore = $this->historyScore($user, $trip, $context['joined_driver_ids'] ?? null);
        $seatScore = $this->seatScore($trip, $context['taken_seats_by_trip'] ?? null);
        $fareScore = $this->fareScore($trip);

        return [
            'route_score' => $routeScore,
            'time_score' => $timePreferenceScore,
            'seat_score' => $seatScore,
            'fare_score' => $fareScore,
            'connection_score' => $connectionScore,
            'history_score' => $historyScore,
        ];
    }

    public function routeDemandFeatures(SavedRoute $route, ?Carbon $tripAt = null): array
    {
        $trips = Trip::query()
            ->where('saved_route_id', $route->id)
            ->where('visibility', 'public')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get();

        $historicalCount = $trips->count();
        $seatLimitAverage = round((float) ($trips->avg('seat_limit') ?? 0), 0);
        $fareAverage = round((float) ($trips->avg('fare_total') ?? (float) $route->default_fare), 2);
        $passengerFillAverage = round((float) $trips->map(function (Trip $trip): float {
            $takenSeats = (int) TripParticipant::query()
                ->where('trip_id', $trip->id)
                ->where('is_driver', false)
                ->count();

            if (! $trip->seat_limit) {
                return 0;
            }

            return ($takenSeats / max(1, (int) $trip->seat_limit)) * 100;
        })->avg(), 2);

        $matchingTimeBucketCount = 0;
        if ($tripAt) {
            $matchingTimeBucketCount = $trips
                ->filter(fn (Trip $trip) => $trip->trip_datetime?->format('H') === $tripAt->format('H'))
                ->count();
        }

        return [
            'historical_trip_count' => $historicalCount,
            'average_seat_limit' => (int) max(0, $seatLimitAverage),
            'average_fare_total' => $fareAverage,
            'average_fill_rate' => $passengerFillAverage,
            'time_bucket_trip_count' => $matchingTimeBucketCount,
        ];
    }

    private function routeAlignmentScore(Collection $savedRoutes, Trip $trip): float
    {
        $pickupName = mb_strtolower((string) $trip->pickup_name);
        $destinationName = mb_strtolower((string) $trip->destination_name);

        foreach ($savedRoutes as $route) {
            $pointA = mb_strtolower((string) $route->point_a_name);
            $pointB = mb_strtolower((string) $route->point_b_name);

            if (($pickupName !== '' && str_contains($pickupName, $pointA)) || ($destinationName !== '' && str_contains($destinationName, $pointB))) {
                return 40.0;
            }
        }

        return 12.0;
    }

    private function timePreferenceScore(?int $preferredHour, Trip $trip): float
    {
        if (! $trip->trip_datetime) {
            return 0.0;
        }

        // Strict null check: preferred_hour === 0 (midnight) is a real value, not
        // "no history" — matches the old isEmpty() branch order exactly.
        if ($preferredHour === null) {
            return 10.0;
        }

        $hourDiff = abs($preferredHour - (int) $trip->trip_datetime->format('H'));

        return max(0.0, 20.0 - ($hourDiff * 2.5));
    }

    /**
     * $connectedDriverIds, when given (rankTripsForUser's batch path), is the
     * precomputed accepted-connection set — avoids one exists() query per trip.
     * Null (scoreTripForUser called alone) falls back to the original query.
     */
    private function connectionScore(User $user, Trip $trip, ?Collection $connectedDriverIds = null): float
    {
        if ($connectedDriverIds !== null) {
            return $connectedDriverIds->contains($trip->driver_id) ? 10.0 : 0.0;
        }

        $isConnected = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($user, $trip): void {
                $query->where('requester_id', $user->id)->where('receiver_id', $trip->driver_id)
                    ->orWhere(function ($pair) use ($user, $trip): void {
                        $pair->where('requester_id', $trip->driver_id)->where('receiver_id', $user->id);
                    });
            })
            ->exists();

        return $isConnected ? 10.0 : 0.0;
    }

    private function historyScore(User $user, Trip $trip, ?Collection $joinedDriverIds = null): float
    {
        if ($joinedDriverIds !== null) {
            return $joinedDriverIds->contains($trip->driver_id) ? 10.0 : 3.0;
        }

        $joinedWithDriverBefore = TripParticipant::query()
            ->where('user_id', $user->id)
            ->where('is_driver', false)
            ->whereHas('trip', fn ($query) => $query->where('driver_id', $trip->driver_id))
            ->exists();

        return $joinedWithDriverBefore ? 10.0 : 3.0;
    }

    private function seatScore(Trip $trip, ?Collection $takenSeatsByTrip = null): float
    {
        if (! $trip->seat_limit) {
            return 5.0;
        }

        $takenSeats = $takenSeatsByTrip !== null
            ? (int) ($takenSeatsByTrip->get($trip->id) ?? 0)
            : (int) TripParticipant::query()
                ->where('trip_id', $trip->id)
                ->where('is_driver', false)
                ->count();

        $available = max(0, (int) $trip->seat_limit - $takenSeats);

        return min(10.0, (float) ($available * 3));
    }

    private function fareScore(Trip $trip): float
    {
        $fare = (float) $trip->fare_per_person;

        if ($fare <= 5) {
            return 10.0;
        }

        if ($fare <= 10) {
            return 7.5;
        }

        if ($fare <= 20) {
            return 5.0;
        }

        return 2.5;
    }
}
