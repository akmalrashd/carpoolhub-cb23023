<?php

namespace App\Services\Ai;

use App\Models\SavedRoute;
use App\Models\Trip;
use App\Models\TripStrategySuggestion;
use App\Models\User;
use Carbon\Carbon;

class FareSeatSuggestionService
{
    public function __construct(
        private readonly FeatureEngineeringService $featureEngineeringService,
    ) {
    }

    public function suggestForDraft(User $driver, SavedRoute $route, array $context = []): array
    {
        $tripAt = ! empty($context['trip_datetime']) ? Carbon::parse((string) $context['trip_datetime']) : null;
        $features = $this->featureEngineeringService->routeDemandFeatures($route, $tripAt);

        $suggestedSeatLimit = max(1, (int) round($features['average_seat_limit'] ?: 3));
        $suggestedFareTotal = round((float) ($features['average_fare_total'] ?: $route->default_fare), 2);
        $includeDriver = (bool) ($context['include_driver_in_split'] ?? true);
        $splitCount = $suggestedSeatLimit + ($includeDriver ? 1 : 0);
        $suggestedFarePerPerson = round($suggestedFareTotal / max(1, $splitCount), 2);
        $demandScore = $this->demandScore($features);

        return [
            'driver_id' => $driver->id,
            'saved_route_id' => $route->id,
            'suggested_fare_total' => $suggestedFareTotal,
            'suggested_fare_per_person' => $suggestedFarePerPerson,
            'suggested_seat_limit' => $suggestedSeatLimit,
            'demand_score' => $demandScore,
            'confidence_level' => $features['historical_trip_count'] >= 5 ? 'Medium' : 'Low',
            'strategy_type' => 'draft_form',
            'input_payload' => $context,
            'explanation_json' => [
                'Historical route count: ' . $features['historical_trip_count'],
                'Average fill rate: ' . number_format((float) $features['average_fill_rate'], 1) . '%',
            ],
        ];
    }

    public function suggestForTrip(Trip $trip): array
    {
        return $this->suggestForDraft($trip->driver, $trip->savedRoute, [
            'trip_datetime' => optional($trip->trip_datetime)->toDateTimeString(),
            'include_driver_in_split' => ((int) $trip->participant_count) > ((int) $trip->participants()->where('is_driver', false)->count()),
        ]);
    }

    public function persistDraftSuggestion(array $suggestion, ?Trip $trip = null): TripStrategySuggestion
    {
        return TripStrategySuggestion::query()->create([
            ...$suggestion,
            'trip_id' => $trip?->id,
        ]);
    }

    private function demandScore(array $features): int
    {
        $score = 0;
        $score += min(40, (int) ($features['historical_trip_count'] * 5));
        $score += min(40, (int) round((float) $features['average_fill_rate'] * 0.4));
        $score += min(20, (int) ($features['time_bucket_trip_count'] * 4));

        return max(0, min(100, $score));
    }
}
