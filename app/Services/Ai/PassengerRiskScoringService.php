<?php

namespace App\Services\Ai;

use App\Models\PassengerRiskProfile;
use App\Models\Trip;
use App\Models\User;
use App\Services\PassengerReliabilityService;
use Illuminate\Support\Collection;

class PassengerRiskScoringService
{
    public function __construct(
        private readonly FeatureEngineeringService $featureEngineeringService,
        private readonly PassengerReliabilityService $passengerReliabilityService,
    ) {
    }

    /**
     * Score every passenger for a trip using ONE batched features query and the
     * caller's already-built reliability map, instead of ~9 queries per
     * passenger. Scores are identical to scoring each passenger individually —
     * passengerRiskFeaturesForUsers returns the same per-user row — so only the
     * query count drops.
     *
     * @param  Collection<int, User>  $passengers
     * @param  array<int, array<string, mixed>>  $reliabilityMap  keyed by user id.
     *   When a caller already built this in a single batched query (e.g.
     *   RefreshController::tripRequests), passing it here avoids re-running
     *   buildForUsers() once per passenger — the value is identical either way.
     * @return array<int, array<string, mixed>>  scores keyed by user id
     */
    public function scoreUsersForTrip(Collection $passengers, Trip $trip, ?User $driver = null, array $reliabilityMap = []): array
    {
        $ids = $passengers->pluck('id')->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        $featuresMap = $this->featureEngineeringService->passengerRiskFeaturesForUsers($ids);

        return $passengers->mapWithKeys(fn (User $passenger) => [
            $passenger->id => $this->scoreUserForTrip(
                $passenger,
                $trip,
                $driver,
                $reliabilityMap[$passenger->id] ?? null,
                $featuresMap[$passenger->id] ?? null,
            ),
        ])->all();
    }

    public function scoreUserForTrip(User $passenger, Trip $trip, ?User $driver = null, ?array $reliability = null, ?array $features = null): array
    {
        $features ??= $this->featureEngineeringService->passengerRiskFeatures($passenger);
        $reliability = $reliability ?? ($this->passengerReliabilityService->buildForUsers([$passenger->id])[$passenger->id] ?? [
            'score' => 5.0,
            'label' => 'Excellent',
            'unpaid_cases' => 0,
            'outstanding_amount' => 0.0,
            'oldest_overdue_days' => 0,
        ]);

        $score = (int) config('ai_decision_support.passenger_risk.score.base', 70);
        $reasons = [];

        if ($features['outstanding_amount'] > 0) {
            $score -= 20;
            $reasons[] = 'Has outstanding payment amount.';
        } else {
            $score += 10;
            $reasons[] = 'No outstanding payment amount.';
        }

        if ($features['overdue_case_count'] > 0) {
            $score -= min(20, $features['overdue_case_count'] * 5);
            $reasons[] = 'Has overdue payment cases.';
        }

        if ($features['cancelled_request_count'] > 1) {
            $score -= min(10, $features['cancelled_request_count'] * 2);
            $reasons[] = 'Repeated cancelled join requests.';
        }

        if ($features['attendance_absent_count'] > 0) {
            $score -= min(20, $features['attendance_absent_count'] * 8);
            $reasons[] = 'Has absent attendance history.';
        }

        if ($driver && $driver->id !== $passenger->id) {
            $score += 5;
            $reasons[] = 'Can be evaluated with driver-specific context later.';
        }

        if ((float) $reliability['score'] >= 4.5) {
            $score += 10;
            $reasons[] = 'Strong payment reliability.';
        } elseif ((float) $reliability['score'] < 3.0) {
            $score -= 15;
            $reasons[] = 'Weak payment reliability.';
        }

        $score = max(
            (int) config('ai_decision_support.passenger_risk.score.min', 0),
            min((int) config('ai_decision_support.passenger_risk.score.max', 100), $score)
        );

        return [
            'score' => $score,
            'risk_level' => $this->riskLevel($score),
            'payment_reliability_score' => (float) $reliability['score'],
            'reasons' => array_values(array_unique($reasons)),
            'features' => $features,
        ];
    }

    public function refreshRiskProfile(User $user): PassengerRiskProfile
    {
        $result = $this->scoreUserForTrip($user, new Trip());

        return PassengerRiskProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'risk_score' => $result['score'],
                'risk_level' => $result['risk_level'],
                'payment_reliability_score' => $result['payment_reliability_score'],
                'join_request_count' => $result['features']['join_request_count'],
                'approved_request_count' => $result['features']['approved_request_count'],
                'rejected_request_count' => $result['features']['rejected_request_count'],
                'cancelled_request_count' => $result['features']['cancelled_request_count'],
                'attendance_absent_count' => $result['features']['attendance_absent_count'],
                'outstanding_amount' => $result['features']['outstanding_amount'],
                'overdue_case_count' => $result['features']['overdue_case_count'],
                'avg_payment_delay_hours' => $result['features']['avg_payment_delay_hours'],
                'last_scored_at' => now(),
                'feature_payload' => $result,
            ]
        );
    }

    private function riskLevel(int $score): string
    {
        foreach ((array) config('ai_decision_support.passenger_risk.bands', []) as $band) {
            if ($score >= (int) ($band['min'] ?? 0) && $score <= (int) ($band['max'] ?? 100)) {
                return (string) ($band['label'] ?? 'Moderate Risk');
            }
        }

        return 'Moderate Risk';
    }
}
