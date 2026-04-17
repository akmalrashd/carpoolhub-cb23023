<?php

namespace App\Services;

use Carbon\Carbon;
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

        $now = now();

        $rows = DB::table('trip_payments as tp')
            ->join('trips as t', 't.id', '=', 'tp.trip_id')
            ->whereIn('tp.user_id', $ids)
            ->whereIn('tp.payment_status', ['unpaid', 'pending_confirmation'])
            ->whereNotIn('t.status', ['draft', 'scheduled'])
            ->groupBy('tp.user_id')
            ->select(
                'tp.user_id',
                DB::raw('COUNT(tp.id) as unpaid_cases'),
                DB::raw('COALESCE(SUM(tp.amount_due), 0) as outstanding_amount'),
                DB::raw("MIN(CASE WHEN t.trip_datetime < NOW() THEN t.trip_datetime ELSE NULL END) as oldest_overdue_trip_datetime")
            )
            ->get();

        $aggregates = $rows->keyBy('user_id');
        $result = [];

        foreach ($ids as $userId) {
            $row = $aggregates->get($userId);
            $unpaidCases = $row ? (int) $row->unpaid_cases : 0;
            $outstandingAmount = $row ? (float) $row->outstanding_amount : 0.0;
            $hasOverdue = $row && ! empty($row->oldest_overdue_trip_datetime);
            $oldestOverdueDays = $this->resolveOldestOverdueDays($row?->oldest_overdue_trip_datetime, $now);

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

    private function resolveOldestOverdueDays(mixed $oldestOverdueDateTime, Carbon $now): int
    {
        if (! $oldestOverdueDateTime) {
            return 0;
        }

        $overdueAt = Carbon::parse($oldestOverdueDateTime);
        return max(0, $overdueAt->diffInDays($now));
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
