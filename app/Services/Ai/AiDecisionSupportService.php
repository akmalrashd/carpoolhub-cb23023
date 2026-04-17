<?php

namespace App\Services\Ai;

use App\Models\SavedRoute;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Collection;

class AiDecisionSupportService
{
    public function __construct(
        private readonly TripMatchingService $tripMatchingService,
        private readonly PassengerRiskScoringService $passengerRiskScoringService,
        private readonly FareSeatSuggestionService $fareSeatSuggestionService,
    ) {
    }

    public function recommendTrips(User $user, Collection $trips, array $filters = [], bool $log = false): Collection
    {
        return $this->tripMatchingService->rankTripsForUser($user, $trips, $filters, $log);
    }

    public function buildPassengerRiskCard(User $passenger, Trip $trip, ?User $driver = null): array
    {
        return $this->passengerRiskScoringService->scoreUserForTrip($passenger, $trip, $driver);
    }

    public function suggestTripStrategy(User $driver, SavedRoute $route, array $context = []): array
    {
        return $this->fareSeatSuggestionService->suggestForDraft($driver, $route, $context);
    }
}
