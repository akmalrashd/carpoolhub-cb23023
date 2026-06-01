<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripPayment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function overview(): array
    {
        return [
            'users_total' => User::query()->count(),
            'drivers_total' => User::query()->where('role', 'driver')->count(),
            'passengers_total' => User::query()->where('role', 'passenger')->count(),
            'active_users_total' => User::query()->where('is_active', true)->count(),
            'trips_total' => Trip::query()->count(),
            'trips_completed' => Trip::query()->whereIn('status', ['recorded', 'completed'])->count(),
            'fare_total' => (float) Trip::query()->sum('fare_total'),
            'payments_total' => (float) TripPayment::query()->sum('amount_due'),
            'payments_paid' => (float) TripPayment::query()->where('payment_status', 'paid')->sum('amount_due'),
            'payments_pending_unpaid' => (float) TripPayment::query()
                ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
                ->sum('amount_due'),
        ];
    }

    public function paymentStatusBreakdown(): array
    {
        $statuses = ['unpaid', 'pending_confirmation', 'paid'];
        $result = [];

        foreach ($statuses as $status) {
            $query = TripPayment::query()->where('payment_status', $status);
            $result[$status] = [
                'count' => (int) (clone $query)->count(),
                'amount' => (float) (clone $query)->sum('amount_due'),
            ];
        }

        return $result;
    }

    public function monthlyTripSummary(int $months = 12): array
    {
        $rows = DB::table('trips')
            ->selectRaw("DATE_FORMAT(trip_datetime, '%Y-%m') as month_key, COUNT(*) as trip_count, COALESCE(SUM(fare_total),0) as fare_total")
            ->whereNotNull('trip_datetime')
            ->groupByRaw("DATE_FORMAT(trip_datetime, '%Y-%m')")
            ->orderByDesc('month_key')
            ->limit($months)
            ->get();

        $paymentRows = DB::table('trip_payments as tp')
            ->join('trips as t', 't.id', '=', 'tp.trip_id')
            ->selectRaw("
                DATE_FORMAT(t.trip_datetime, '%Y-%m') as month_key,
                COALESCE(SUM(CASE WHEN tp.payment_status = 'paid' THEN tp.amount_due ELSE 0 END),0) as paid_total,
                COALESCE(SUM(CASE WHEN tp.payment_status IN ('unpaid','pending_confirmation') THEN tp.amount_due ELSE 0 END),0) as pending_unpaid_total
            ")
            ->whereNotNull('t.trip_datetime')
            ->groupByRaw("DATE_FORMAT(t.trip_datetime, '%Y-%m')")
            ->get()
            ->keyBy('month_key');

        return $rows->map(function ($row) use ($paymentRows) {
            $pay = $paymentRows->get($row->month_key);
            return [
                'month_key' => $row->month_key,
                'trip_count' => (int) $row->trip_count,
                'fare_total' => (float) $row->fare_total,
                'paid_total' => (float) ($pay->paid_total ?? 0),
                'pending_unpaid_total' => (float) ($pay->pending_unpaid_total ?? 0),
            ];
        })->values()->all();
    }

    public function monthlyTripSummaryForExport(): array
    {
        return $this->monthlyTripSummary(24);
    }
}
