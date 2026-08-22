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

    /**
     * Fuzzy-logic risk scoring (Sugeno-style: fuzzify each feature into
     * linguistic-term membership degrees, evaluate a small IF-THEN rule base,
     * defuzzify each feature group as a membership-weighted average, then sum
     * with the base score). Replaces the earlier crisp "if count > 0" rules,
     * which caused hard cliffs (e.g. 1 overdue case landing the same -20 as
     * 10 overdue cases) instead of a graded response. Rule constants are
     * calibrated so a fully-clean record and a fully-bad record land on the
     * same scores the old crisp version produced — only the transition
     * between them is now smooth instead of a step function.
     */
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

        $base = (float) config('ai_decision_support.passenger_risk.score.base', 70);
        $reasons = [];

        // Fuzzification: crisp feature values → membership degree (0.0-1.0)
        // per linguistic term. Ramp widths are chosen so the linear region
        // reproduces the old crisp rule's per-unit penalty exactly; only the
        // sudden 0→1 jump at the threshold is replaced with a ramp.
        $overdue = $this->fuzzifyCount((float) $features['overdue_case_count'], rampStart: 0, rampEnd: 4);
        $cancelled = $this->fuzzifyCount((float) $features['cancelled_request_count'], rampStart: 1, rampEnd: 5);
        $absent = $this->fuzzifyCount((float) $features['attendance_absent_count'], rampStart: 0, rampEnd: 2.5);
        $reliabilityFuzzy = $this->fuzzifyReliability((float) $reliability['score']);
        $outstandingFuzzy = $this->fuzzifyOutstanding((float) $features['outstanding_amount']);

        // Rule base (Sugeno zero-order: each rule's consequent is a constant
        // score adjustment). Firing strength = that term's membership degree;
        // a feature's net contribution is the membership-weighted average
        // across its own terms, so partial membership in two terms at once
        // (e.g. 60% "none" + 40% "many") blends smoothly instead of picking one.
        $overdueAdj = $this->weightedRule($overdue, ['none' => 0.0, 'many' => -20.0]);
        $cancelledAdj = $this->weightedRule($cancelled, ['none' => 0.0, 'many' => -10.0]);
        $absentAdj = $this->weightedRule($absent, ['none' => 0.0, 'many' => -20.0]);
        $reliabilityAdj = $this->weightedRule($reliabilityFuzzy, ['low' => -15.0, 'medium' => 0.0, 'high' => 10.0]);
        $outstandingAdj = $this->weightedRule($outstandingFuzzy, ['none' => 10.0, 'some' => -5.0, 'high' => -20.0]);

        if ($overdue['many'] >= 0.4) {
            $reasons[] = 'Has overdue payment cases.';
        }
        if ($cancelled['many'] >= 0.4) {
            $reasons[] = 'Repeated cancelled join requests.';
        }
        if ($absent['many'] >= 0.4) {
            $reasons[] = 'Has absent attendance history.';
        }
        if ($reliabilityFuzzy['high'] >= 0.5) {
            $reasons[] = 'Strong payment reliability.';
        } elseif ($reliabilityFuzzy['low'] >= 0.4) {
            $reasons[] = 'Weak payment reliability.';
        }
        if ($outstandingFuzzy['none'] >= 0.5) {
            $reasons[] = 'No outstanding payment amount.';
        } elseif ($outstandingFuzzy['high'] >= 0.4 || $outstandingFuzzy['some'] >= 0.4) {
            $reasons[] = 'Has outstanding payment amount.';
        }

        $score = $base + $overdueAdj + $cancelledAdj + $absentAdj + $reliabilityAdj + $outstandingAdj;

        if ($driver && $driver->id !== $passenger->id) {
            $score += 5;
            $reasons[] = 'Can be evaluated with driver-specific context later.';
        }

        // Defuzzification: sum of weighted rule outputs is already a crisp
        // number here (Sugeno models skip the centroid step Mamdani needs) —
        // just clamp to the configured band.
        $score = (int) round(max(
            (int) config('ai_decision_support.passenger_risk.score.min', 0),
            min((int) config('ai_decision_support.passenger_risk.score.max', 100), $score)
        ));

        return [
            'score' => $score,
            'risk_level' => $this->riskLevel($score),
            'payment_reliability_score' => (float) $reliability['score'],
            'reasons' => array_values(array_unique($reasons)),
            'features' => $features,
            'fuzzy_memberships' => [
                'overdue' => $overdue,
                'cancelled' => $cancelled,
                'attendance_absent' => $absent,
                'reliability' => $reliabilityFuzzy,
                'outstanding' => $outstandingFuzzy,
            ],
        ];
    }

    /**
     * Membership-weighted average of a rule base's constant outputs for one
     * feature's fuzzy set — the Sugeno-style defuzzification for that group.
     * Normalising by total membership keeps this correct even when a
     * feature's terms don't sum to exactly 1 (rounding, or a 3-term set with
     * a gap between its two anchor trapezoids).
     */
    private function weightedRule(array $memberships, array $ruleOutputs): float
    {
        $totalWeight = array_sum($memberships);
        if ($totalWeight <= 0.0) {
            return 0.0;
        }

        $weightedSum = 0.0;
        foreach ($memberships as $term => $degree) {
            $weightedSum += $degree * ($ruleOutputs[$term] ?? 0.0);
        }

        return $weightedSum / $totalWeight;
    }

    /**
     * Fuzzifies a non-negative count feature (overdue cases, cancellations,
     * absences) into {none, many}, ramping linearly from $rampStart to
     * $rampEnd. The two terms are complementary by construction (always sum
     * to 1), so this is a direct smoothing of the old "count > threshold"
     * cliff rather than a new judgement call.
     */
    private function fuzzifyCount(float $count, float $rampStart, float $rampEnd): array
    {
        return [
            'none' => $this->trapezoid($count, -1, -1, $rampStart, $rampEnd),
            'many' => $this->trapezoid($count, $rampStart, $rampEnd, PHP_FLOAT_MAX, PHP_FLOAT_MAX),
        ];
    }

    /**
     * Fuzzifies the 0-5 payment reliability score into {low, medium, high}.
     * low/high anchor at the old crisp thresholds (< 3.0 / >= 4.5); medium
     * fills whatever's left so the three terms always sum to 1.
     */
    private function fuzzifyReliability(float $score): array
    {
        $low = $this->trapezoid($score, -1, -1, 2.0, 3.0);
        $high = $this->trapezoid($score, 3.5, 4.5, PHP_FLOAT_MAX, PHP_FLOAT_MAX);

        return [
            'low' => $low,
            'medium' => max(0.0, 1.0 - $low - $high),
            'high' => $high,
        ];
    }

    /**
     * Fuzzifies the outstanding payment amount (RM) into {none, some, high}.
     * The old rule only had a binary "owes anything at all" cliff (-20 flat);
     * this adds a genuine middle band so a small unpaid amount isn't treated
     * as harshly as a large one.
     */
    private function fuzzifyOutstanding(float $amount): array
    {
        $none = $this->trapezoid($amount, -1, -1, 0, 15);
        $high = $this->trapezoid($amount, 30, 60, PHP_FLOAT_MAX, PHP_FLOAT_MAX);

        return [
            'none' => $none,
            'some' => max(0.0, 1.0 - $none - $high),
            'high' => $high,
        ];
    }

    /** Trapezoidal membership function: 0 outside (a,d), plateau at 1.0 across [b,c]. */
    private function trapezoid(float $x, float $a, float $b, float $c, float $d): float
    {
        if ($x <= $a || $x >= $d) {
            return 0.0;
        }
        if ($x >= $b && $x <= $c) {
            return 1.0;
        }

        return $x < $b ? ($x - $a) / ($b - $a) : ($d - $x) / ($d - $c);
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
