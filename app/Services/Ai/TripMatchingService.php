<?php

namespace App\Services\Ai;

use App\Models\Trip;
use App\Models\TripRecommendationLog;
use App\Models\User;
use Illuminate\Support\Collection;

class TripMatchingService
{
    public function __construct(
        private readonly FeatureEngineeringService $featureEngineeringService,
    ) {
    }

    public function rankTripsForUser(User $user, Collection $trips, array $filters = [], bool $log = false): Collection
    {
        // The user's saved routes and preferred hour do not change between trips,
        // so compute them once here instead of once per trip inside the map.
        $context = $this->featureEngineeringService->recommendationContext($user);

        return $trips
            ->map(function (Trip $trip) use ($user, $log, $context): array {
                $score = $this->scoreTripForUser($user, $trip, $log, $context);

                return [
                    'trip' => $trip,
                    'score' => $score,
                ];
            })
            ->sortByDesc(fn (array $row) => $row['score']['match_score'])
            ->values();
    }

    public function scoreTripForUser(User $user, Trip $trip, bool $log = false, ?array $context = null): array
    {
        $features = $this->featureEngineeringService->recommendationFeatures($user, $trip, $context);
        $matchScore = round(array_sum($features), 2);
        $explanations = $this->buildExplanation($features);

        $result = [
            ...$features,
            'match_score' => $matchScore,
            'explanations' => $explanations,
        ];

        if ($log) {
            TripRecommendationLog::query()->create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'context_source' => 'explore',
                'match_score' => $matchScore,
                'route_score' => $features['route_score'],
                'time_score' => $features['time_score'],
                'seat_score' => $features['seat_score'],
                'connection_score' => $features['connection_score'],
                'fare_score' => $features['fare_score'],
                'history_score' => $features['history_score'],
                'explanation_json' => $explanations,
            ]);
        }

        return $result;
    }

    private function buildExplanation(array $features): array
    {
        $messages = [];

        if (($features['route_score'] ?? 0) >= 30) {
            $messages[] = 'Near your saved route.';
        }
        if (($features['time_score'] ?? 0) >= 15) {
            $messages[] = 'Matches your typical travel time.';
        }
        if (($features['connection_score'] ?? 0) > 0) {
            $messages[] = 'Driver is already in your connections.';
        }
        if (($features['seat_score'] ?? 0) >= 6) {
            $messages[] = 'Good seat availability.';
        }
        if (($features['fare_score'] ?? 0) >= 7.5) {
            $messages[] = 'Fare is in a favorable range.';
        }

        return array_slice($messages, 0, 3);
    }
}
