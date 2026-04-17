<?php

namespace App\Services;

use App\Models\BillingCycle;
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

    public function cycleReports(int $perPage = 12): LengthAwarePaginator
    {
        $cycles = BillingCycle::query()
            ->latest('start_date')
            ->paginate($perPage);

        return $this->attachCycleAggregates($cycles);
    }

    public function cycleReportsForExport()
    {
        $cycles = BillingCycle::query()
            ->latest('start_date')
            ->get();

        return $this->attachCycleAggregates($cycles);
    }

    private function attachCycleAggregates($cycles)
    {
        $collection = method_exists($cycles, 'getCollection') ? $cycles->getCollection() : $cycles;

        $cycleIds = $collection->pluck('id');
        $tripAgg = Trip::query()
            ->selectRaw('billing_cycle_id, COUNT(*) as trip_count, COALESCE(SUM(fare_total),0) as fare_total')
            ->whereIn('billing_cycle_id', $cycleIds)
            ->groupBy('billing_cycle_id')
            ->get()
            ->keyBy('billing_cycle_id');

        $paymentAgg = DB::table('trip_payments as tp')
            ->join('trips as t', 't.id', '=', 'tp.trip_id')
            ->selectRaw("
                t.billing_cycle_id as billing_cycle_id,
                COALESCE(SUM(CASE WHEN tp.payment_status = 'paid' THEN tp.amount_due ELSE 0 END),0) as paid_total,
                COALESCE(SUM(CASE WHEN tp.payment_status IN ('unpaid','pending_confirmation') THEN tp.amount_due ELSE 0 END),0) as pending_unpaid_total
            ")
            ->whereIn('t.billing_cycle_id', $cycleIds)
            ->groupBy('t.billing_cycle_id')
            ->get()
            ->keyBy('billing_cycle_id');

        $collection->transform(function ($cycle) use ($tripAgg, $paymentAgg) {
            $tripRow = $tripAgg->get($cycle->id);
            $paymentRow = $paymentAgg->get($cycle->id);
            $cycle->report_trip_count = (int) ($tripRow->trip_count ?? 0);
            $cycle->report_fare_total = (float) ($tripRow->fare_total ?? 0);
            $cycle->report_paid_total = (float) ($paymentRow->paid_total ?? 0);
            $cycle->report_pending_unpaid_total = (float) ($paymentRow->pending_unpaid_total ?? 0);

            return $cycle;
        });

        return $cycles;
    }
}
