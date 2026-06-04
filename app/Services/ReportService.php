<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripPayment;
use App\Models\User;
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
            'public_trips_total' => Trip::query()->where('visibility', 'public')->count(),
            'custom_route_requests_total' => DB::table('trip_passenger_route_points')
                ->where(function ($query): void {
                    $query->where('uses_default_pickup', false)
                        ->orWhere('uses_default_dropoff', false);
                })
                ->count(),
            'join_requests_total' => DB::table('trip_join_requests')->count(),
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

    public function dailyTripRanges(): array
    {
        return [
            '7d' => $this->dailyTripCounts(7),
            '30d' => $this->dailyTripCounts(30),
            '90d' => $this->dailyTripCounts(90),
        ];
    }

    public function dailyTripCounts(int $days = 30): array
    {
        $days = max(1, min($days, 180));
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $rows = DB::table('trips')
            ->selectRaw('DATE(trip_datetime) as day_key, COUNT(*) as trip_count')
            ->whereBetween('trip_datetime', [$start, $end])
            ->groupByRaw('DATE(trip_datetime)')
            ->get()
            ->keyBy('day_key');

        $result = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $result[$key] = (int) ($rows->get($key)->trip_count ?? 0);
        }

        return $result;
    }

    public function topRoutes(int $limit = 8): array
    {
        $routeExpression = "COALESCE(sr.route_name, CONCAT(COALESCE(t.pickup_name, 'Pickup'), ' -> ', COALESCE(t.destination_name, 'Destination')))";

        return DB::table('trips as t')
            ->leftJoin('saved_routes as sr', 'sr.id', '=', 't.saved_route_id')
            ->selectRaw("
                {$routeExpression} as route_name,
                COUNT(*) as trip_count,
                COUNT(DISTINCT t.driver_id) as driver_count,
                COALESCE(AVG(t.fare_total), 0) as avg_fare,
                COALESCE(SUM(t.fare_total), 0) as fare_total
            ")
            ->where('t.is_return_trip', false)
            ->groupByRaw($routeExpression)
            ->orderByDesc('trip_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'route_name' => $row->route_name,
                'trip_count' => (int) $row->trip_count,
                'driver_count' => (int) $row->driver_count,
                'avg_fare' => (float) $row->avg_fare,
                'fare_total' => (float) $row->fare_total,
            ])
            ->all();
    }

    public function requestDecisionSummary(): array
    {
        $rows = DB::table('trip_join_requests')
            ->selectRaw('status, COUNT(*) as total, COALESCE(AVG(decision_duration_minutes), 0) as avg_minutes')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $total = (int) $rows->sum('total');
        $approved = (int) ($rows->get('approved')->total ?? 0);
        $pending = (int) ($rows->get('pending')->total ?? 0);
        $decided = max(1, $total - $pending);

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => (int) ($rows->get('rejected')->total ?? 0),
            'cancelled' => (int) ($rows->get('cancelled')->total ?? 0),
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0.0,
            'avg_decision_minutes' => round((float) $rows->avg('avg_minutes'), 1),
            'decided_count' => $decided,
        ];
    }

    public function customRouteSummary(): array
    {
        $query = DB::table('trip_passenger_route_points');
        $customQuery = (clone $query)->where(function ($inner): void {
            $inner->where('uses_default_pickup', false)
                ->orWhere('uses_default_dropoff', false);
        });

        $total = (int) (clone $query)->count();
        $custom = (int) (clone $customQuery)->count();
        $accepted = (int) (clone $customQuery)->whereIn('status', ['accepted', 'approved'])->count();

        return [
            'total_route_points' => $total,
            'custom_requests' => $custom,
            'accepted_custom_requests' => $accepted,
            'custom_share' => $total > 0 ? round(($custom / $total) * 100, 1) : 0.0,
            'avg_detour_km' => round((float) (clone $customQuery)->avg('detour_distance_km'), 2),
            'avg_extra_fee' => round((float) (clone $customQuery)->avg('extra_fee_amount'), 2),
            'extra_fee_total' => round((float) (clone $customQuery)->sum('extra_fee_amount'), 2),
        ];
    }

    public function aiSupportSummary(): array
    {
        $recommendations = DB::table('trip_recommendation_logs');
        $strategy = DB::table('trip_strategy_suggestions');

        return [
            'recommendation_logs' => (int) (clone $recommendations)->count(),
            'avg_match_score' => round((float) (clone $recommendations)->avg('match_score'), 1),
            'strategy_suggestions' => (int) (clone $strategy)->count(),
            'high_confidence_suggestions' => (int) (clone $strategy)->where('confidence_level', 'High')->count(),
            'avg_demand_score' => round((float) (clone $strategy)->avg('demand_score'), 1),
        ];
    }

    public function passengerReliabilitySummary(): array
    {
        $rows = DB::table('passenger_risk_profiles')
            ->selectRaw('risk_level, COUNT(*) as total, COALESCE(AVG(risk_score), 0) as avg_score')
            ->groupBy('risk_level')
            ->get();

        $totalProfiles = (int) $rows->sum('total');
        $highRisk = (int) $rows
            ->whereIn('risk_level', ['High Risk', 'Very High Risk'])
            ->sum('total');

        return [
            'profiles_total' => $totalProfiles,
            'high_risk_total' => $highRisk,
            'avg_risk_score' => round((float) $rows->avg('avg_score'), 1),
            'avg_payment_reliability' => round((float) DB::table('passenger_risk_profiles')->avg('payment_reliability_score'), 1),
            'outstanding_amount' => round((float) DB::table('passenger_risk_profiles')->sum('outstanding_amount'), 2),
            'by_level' => $rows->map(fn ($row) => [
                'risk_level' => $row->risk_level,
                'total' => (int) $row->total,
                'avg_score' => round((float) $row->avg_score, 1),
            ])->values()->all(),
        ];
    }

    public function thesisAlignmentSummary(): array
    {
        return [
            [
                'objective' => 'AI-assisted trip drafting',
                'evidence' => (int) DB::table('trip_strategy_suggestions')->count(),
                'unit' => 'AI fare/seat suggestions',
            ],
            [
                'objective' => 'Custom route preference',
                'evidence' => (int) DB::table('trip_passenger_route_points')
                    ->where(function ($query): void {
                        $query->where('uses_default_pickup', false)
                            ->orWhere('uses_default_dropoff', false);
                    })
                    ->count(),
                'unit' => 'custom pickup/drop-off records',
            ],
            [
                'objective' => 'Decision support for reliability',
                'evidence' => (int) DB::table('passenger_risk_profiles')->count(),
                'unit' => 'scored passenger profiles',
            ],
            [
                'objective' => 'Structured payment tracking',
                'evidence' => (int) TripPayment::query()->count(),
                'unit' => 'payment records',
            ],
        ];
    }
}
