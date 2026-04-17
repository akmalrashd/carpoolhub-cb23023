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

    public function recommendationFeatures(User $user, Trip $trip): array
    {
        $savedRoutes = SavedRoute::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $timePreferenceScore = $this->timePreferenceScore($user, $trip);
        $routeScore = $this->routeAlignmentScore($savedRoutes, $trip);
        $connectionScore = $this->connectionScore($user, $trip);
        $historyScore = $this->historyScore($user, $trip);
        $seatScore = $this->seatScore($trip);
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

    private function timePreferenceScore(User $user, Trip $trip): float
    {
        if (! $trip->trip_datetime) {
            return 0.0;
        }

        $pastHours = TripParticipant::query()
            ->where('user_id', $user->id)
            ->where('is_driver', false)
            ->whereHas('trip', fn ($query) => $query->whereNotNull('trip_datetime'))
            ->with('trip:id,trip_datetime')
            ->get()
            ->map(fn (TripParticipant $participant) => (int) $participant->trip->trip_datetime->format('H'));

        if ($pastHours->isEmpty()) {
            return 10.0;
        }

        $preferredHour = (int) round($pastHours->avg());
        $hourDiff = abs($preferredHour - (int) $trip->trip_datetime->format('H'));

        return max(0.0, 20.0 - ($hourDiff * 2.5));
    }

    private function connectionScore(User $user, Trip $trip): float
    {
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

    private function historyScore(User $user, Trip $trip): float
    {
        $joinedWithDriverBefore = TripParticipant::query()
            ->where('user_id', $user->id)
            ->where('is_driver', false)
            ->whereHas('trip', fn ($query) => $query->where('driver_id', $trip->driver_id))
            ->exists();

        return $joinedWithDriverBefore ? 10.0 : 3.0;
    }

    private function seatScore(Trip $trip): float
    {
        if (! $trip->seat_limit) {
            return 5.0;
        }

        $takenSeats = (int) TripParticipant::query()
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
