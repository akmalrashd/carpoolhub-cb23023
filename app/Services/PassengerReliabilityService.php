<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PassengerReliabilityService
{
    public function buildForUsers(Collection|array $userIds): array
    {
        $ids = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $graceDays = (int) config('passenger_reliability.driver_review_grace_days', 3);

        // The clock doesn't start at trip_datetime — it starts at the point
        // a passenger could reasonably be expected to have settled it: the
        // day the monthly summary reporting that trip is sent, plus a
        // settle-up window (see config/passenger_reliability.php). LAST_DAY
        // + offset lands on "summary_day_of_month of the following month"
        // regardless of which day in its own month the trip fell on, since
        // every trip in a given month is reported by the same summary run.
        $overdueGraceOffsetDays = (int) config('passenger_reliability.overdue_grace.summary_day_of_month', 3)
            + (int) config('passenger_reliability.overdue_grace.days_after_summary', 14);

        // A plain "unpaid" row counts overdue days from that grace point
        // against real NOW — the passenger is the one blocking. A
        // "pending_confirmation" row instead counts against
        // LEAST(NOW(), marked_paid_at + driver review grace): while the
        // driver is still inside their own review window that's the same as
        // NOW, but once the driver blows past it, the clock freezes there
        // instead of continuing to grow — from that point on, further delay
        // is on the driver, not the passenger. See config/passenger_reliability.php
        // for why neither of these can be gamed by marking paid dishonestly.
        $rows = DB::table('trip_payments as tp')
            ->join('trips as t', 't.id', '=', 'tp.trip_id')
            ->whereIn('tp.user_id', $ids)
            ->whereIn('tp.payment_status', ['unpaid', 'pending_confirmation'])
            ->whereNotIn('t.status', ['draft', 'scheduled'])
            ->groupBy('tp.user_id')
            ->selectRaw(
                'tp.user_id, ' .
                'COUNT(tp.id) as unpaid_cases, ' .
                'COALESCE(SUM(tp.amount_due), 0) as outstanding_amount, ' .
                'MAX(CASE
                    WHEN tp.payment_status = "pending_confirmation" AND tp.marked_paid_at IS NOT NULL
                        THEN GREATEST(0, TIMESTAMPDIFF(DAY, DATE_ADD(LAST_DAY(t.trip_datetime), INTERVAL ? DAY), LEAST(NOW(), DATE_ADD(tp.marked_paid_at, INTERVAL ? DAY))))
                    ELSE GREATEST(0, TIMESTAMPDIFF(DAY, DATE_ADD(LAST_DAY(t.trip_datetime), INTERVAL ? DAY), NOW()))
                END) as oldest_overdue_days',
                [$overdueGraceOffsetDays, $graceDays, $overdueGraceOffsetDays]
            )
            ->get();

        $aggregates = $rows->keyBy('user_id');
        $result = [];

        foreach ($ids as $userId) {
            $row = $aggregates->get($userId);
            $unpaidCases = $row ? (int) $row->unpaid_cases : 0;
            $outstandingAmount = $row ? (float) $row->outstanding_amount : 0.0;
            $oldestOverdueDays = $row ? max(0, (int) $row->oldest_overdue_days) : 0;
            $hasOverdue = $oldestOverdueDays > 0;

            $amountPenalty = $this->penaltyForAmount($outstandingAmount);
            $overduePenalty = $this->penaltyForOverdue($oldestOverdueDays, $hasOverdue);
            $casePenalty = $this->penaltyForCaseCount($unpaidCases);

            $baseScore = (float) config('passenger_reliability.score.base', 5.0);
            $scoreMin = (float) config('passenger_reliability.score.min', 1.0);
            $scoreMax = (float) config('passenger_reliability.score.max', 5.0);
            $precision = (int) config('passenger_reliability.score.precision', 1);

            $score = $baseScore - $amountPenalty - $overduePenalty - $casePenalty;
            $score = max($scoreMin, min($scoreMax, $score));
            $score = round($score, $precision);

            $result[$userId] = [
                'score' => $score,
                'label' => $this->labelForScore($score),
                'unpaid_cases' => $unpaidCases,
                'outstanding_amount' => round($outstandingAmount, 2),
                'oldest_overdue_days' => $oldestOverdueDays,
                'penalties' => [
                    'amount' => $amountPenalty,
                    'overdue' => $overduePenalty,
                    'cases' => $casePenalty,
                ],
            ];
        }

        return $result;
    }

    private function penaltyForAmount(float $amount): float
    {
        return $this->resolvePenalty(
            $amount,
            (array) config('passenger_reliability.amount_penalties', [])
        );
    }

    private function penaltyForOverdue(int $days, bool $hasOverdue): float
    {
        if (! $hasOverdue) {
            return 0.0;
        }

        return $this->resolvePenalty(
            $days,
            (array) config('passenger_reliability.overdue_penalties', [])
        );
    }

    private function penaltyForCaseCount(int $count): float
    {
        return $this->resolvePenalty(
            $count,
            (array) config('passenger_reliability.case_penalties', [])
        );
    }

    private function resolvePenalty(float|int $value, array $ranges): float
    {
        foreach ($ranges as $range) {
            $min = isset($range['min']) ? (float) $range['min'] : null;
            $max = array_key_exists('max', $range) && $range['max'] !== null ? (float) $range['max'] : null;

            if ($min !== null && $value < $min) {
                continue;
            }

            if ($max !== null && $value > $max) {
                continue;
            }

            return (float) ($range['penalty'] ?? 0);
        }

        return 0.0;
    }

    private function labelForScore(float $score): string
    {
        $ranges = (array) config('passenger_reliability.risk_labels', []);

        foreach ($ranges as $range) {
            $min = isset($range['min']) ? (float) $range['min'] : null;
            $max = isset($range['max']) ? (float) $range['max'] : null;

            if ($min !== null && $score < $min) {
                continue;
            }

            if ($max !== null && $score > $max) {
                continue;
            }

            return (string) ($range['label'] ?? 'Moderate');
        }

        return 'Moderate';
    }
}
