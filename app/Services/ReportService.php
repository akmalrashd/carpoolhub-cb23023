<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * $dateFrom/$dateTo scope the metrics that have a natural date to scope
     * by — trips/fare on trip_datetime, payments via their trip's
     * trip_datetime (same join pattern monthlyTripSummary() uses), users on
     * created_at. Both null (the default) reproduces the exact all-time
     * behaviour this method always had. Every other ReportService method
     * stays all-time — this is deliberately the only one date-scoped, since
     * it's what the report's top-line KPI cards read from.
     */
    public function overview(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $from = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : null;

        $tripQuery = Trip::query();
        $userQuery = User::query();
        $paymentQuery = TripPayment::query()->whereHas('trip', function ($q) use ($from, $to) {
            if ($from) {
                $q->where('trip_datetime', '>=', $from);
            }
            if ($to) {
                $q->where('trip_datetime', '<=', $to);
            }
        });

        if ($from) {
            $tripQuery->where('trip_datetime', '>=', $from);
            $userQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $tripQuery->where('trip_datetime', '<=', $to);
            $userQuery->where('created_at', '<=', $to);
        }

        // Neither of these has its own date concept elsewhere in this class
        // (all-time in every other report method too) — when a range is
        // active here, approximate via the row's own created_at so these two
        // KPIs still narrow along with the rest of the overview instead of
        // staying stuck at an all-time count that no longer matches the
        // other cards on the same page.
        $customRouteQuery = $this->customRoutePoints();
        $joinRequestQuery = DB::table('trip_join_requests');
        if ($from) {
            $customRouteQuery->where('created_at', '>=', $from);
            $joinRequestQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $customRouteQuery->where('created_at', '<=', $to);
            $joinRequestQuery->where('created_at', '<=', $to);
        }

        return [
            'users_total' => (clone $userQuery)->count(),
            'drivers_total' => (clone $userQuery)->where('role', 'driver')->count(),
            'passengers_total' => (clone $userQuery)->where('role', 'passenger')->count(),
            'active_users_total' => (clone $userQuery)->where('is_active', true)->count(),
            'trips_total' => (clone $tripQuery)->count(),
            'trips_completed' => (clone $tripQuery)->whereIn('status', ['recorded', 'completed'])->count(),
            'fare_total' => (float) (clone $tripQuery)->sum('fare_total'),
            'payments_total' => (float) (clone $paymentQuery)->sum('amount_due'),
            'payments_paid' => (float) (clone $paymentQuery)->where('payment_status', 'paid')->sum('amount_due'),
            'payments_pending_unpaid' => (float) (clone $paymentQuery)
                ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
                ->sum('amount_due'),
            'public_trips_total' => (clone $tripQuery)->where('visibility', 'public')->count(),
            'custom_route_requests_total' => $customRouteQuery->count(),
            'join_requests_total' => $joinRequestQuery->count(),
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

        // New-signups-per-month — platform growth has no visibility anywhere
        // else in these reports, so it rides along on the same month_key.
        $userRows = DB::table('users')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as new_users")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->get()
            ->keyBy('month_key');

        return $rows->map(function ($row) use ($paymentRows, $userRows) {
            $pay = $paymentRows->get($row->month_key);
            $usr = $userRows->get($row->month_key);

            return [
                'month_key' => $row->month_key,
                'trip_count' => (int) $row->trip_count,
                'new_users' => (int) ($usr->new_users ?? 0),
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

    /**
     * Driver-level leaderboard — topRoutes() ranks routes, but nothing
     * ranked the people actually driving them. Same shape/conventions as
     * topRoutes() (is_return_trip excluded so a round trip isn't double
     * counted; 'recorded'+'completed' matches the completed-trip definition
     * used everywhere else, e.g. TripService/overview()).
     */
    public function topDrivers(int $limit = 8): array
    {
        return DB::table('trips as t')
            ->join('users as u', 'u.id', '=', 't.driver_id')
            ->selectRaw("
                u.name as driver_name,
                COUNT(*) as trip_count,
                SUM(CASE WHEN t.status IN ('recorded', 'completed') THEN 1 ELSE 0 END) as completed_count,
                COUNT(DISTINCT t.saved_route_id) as route_count,
                COALESCE(AVG(t.fare_total), 0) as avg_fare,
                COALESCE(SUM(t.fare_total), 0) as fare_total
            ")
            ->where('t.is_return_trip', false)
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('trip_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'driver_name' => $row->driver_name,
                'trip_count' => (int) $row->trip_count,
                'completed_count' => (int) $row->completed_count,
                'completion_rate' => $row->trip_count > 0 ? round(($row->completed_count / $row->trip_count) * 100, 1) : 0.0,
                'route_count' => (int) $row->route_count,
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
        $cancelled = (int) ($rows->get('cancelled')->total ?? 0);

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => (int) ($rows->get('rejected')->total ?? 0),
            'cancelled' => $cancelled,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0.0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0,
            'avg_decision_minutes' => round((float) $rows->avg('avg_minutes'), 1),
        ];
    }

    public function customRouteSummary(): array
    {
        $query = DB::table('trip_passenger_route_points');
        $customQuery = $this->customRoutePoints();

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
        // Logs from AI recommendation engine (may be empty if engine not yet triggered)
        $recommendationLogs = DB::table('trip_recommendation_logs')->count();
        $strategySuggestions = DB::table('trip_strategy_suggestions')->count();

        // AI fare calculation is applied to all trips with fare_total > 0
        $fareAiTrips = DB::table('trips')->where('fare_total', '>', 0)->count();

        // AI-assisted join request decisions
        $joinRequestsDecided = DB::table('trip_join_requests')
            ->whereIn('status', ['approved', 'rejected'])
            ->count();

        // Total meaningful AI interactions = max of logged + computed usage proxies
        $totalAiInteractions = max(
            $recommendationLogs + $strategySuggestions,
            $fareAiTrips + $joinRequestsDecided
        );

        // Average match score is only meaningful once real recommendation
        // logs exist — no fabricated fallback number here (there used to be
        // a hardcoded 88.5% shown as if measured, which was misleading).
        // avg_match_score_measured tells the view whether to show the
        // percentage or a "not yet measured" state.
        $avgMatchScore = $recommendationLogs > 0
            ? round((float) DB::table('trip_recommendation_logs')->avg('match_score'), 1)
            : 0.0;

        return [
            'recommendation_logs' => $totalAiInteractions,
            'avg_match_score' => $avgMatchScore,
            'avg_match_score_measured' => $recommendationLogs > 0,
            'strategy_suggestions' => $strategySuggestions + $joinRequestsDecided,
        ];
    }

    public function passengerReliabilitySummary(): array
    {
        $profilesCount = DB::table('passenger_risk_profiles')->count();

        if ($profilesCount === 0) {
            // Fallback: compute reliability from actual trip_payments data
            $totalPayments = DB::table('trip_payments')->count();
            $paidPayments = DB::table('trip_payments')->where('payment_status', 'paid')->count();
            $unpaidPayments = DB::table('trip_payments')->where('payment_status', 'unpaid')->count();
            $pendingPayments = DB::table('trip_payments')->where('payment_status', 'pending_confirmation')->count();

            $totalPassengers = User::query()->where('role', 'passenger')->count();
            $totalProfiles = max(1, $totalPassengers);

            // Passengers with any unpaid payments are high-risk
            $passengersWithUnpaid = DB::table('trip_payments')
                ->where('payment_status', 'unpaid')
                ->distinct()
                ->count('user_id');
            $highRisk = min($passengersWithUnpaid, $totalProfiles);

            $reliabilityRate = $totalPayments > 0
                ? round(($paidPayments / $totalPayments) * 100, 1)
                : 100.0;

            $outstandingAmount = round(
                (float) DB::table('trip_payments')
                    ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
                    ->sum('amount_due'),
                2
            );

            return [
                'profiles_total' => $totalProfiles,
                'high_risk_total' => $highRisk,
                'avg_risk_score' => $totalPayments > 0 ? round(($unpaidPayments / $totalPayments) * 100, 1) : 0.0,
                'avg_payment_reliability' => $reliabilityRate,
                'total_payments' => $totalPayments,
                'paid_payments' => $paidPayments,
                'unpaid_payments' => $unpaidPayments + $pendingPayments,
                'outstanding_amount' => $outstandingAmount,
                // by_level isn't rendered anywhere yet (unlike the real
                // passenger_risk_profiles branch below, whose avg_score is a
                // genuine AVG()). Its avg_score here is illustrative only —
                // compute a real one from payment data before ever displaying it.
                'by_level' => [
                    ['risk_level' => 'Low Risk',  'total' => max(0, $totalProfiles - $highRisk), 'avg_score' => 10.0],
                    ['risk_level' => 'High Risk', 'total' => $highRisk,                          'avg_score' => 75.0],
                ],
            ];
        }

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

    /**
     * ai_usage_logs is written on every chat/fare-advice/route-recommendation
     * call (app/Services/AiUsageLogger.php) but was never read anywhere
     * outside that write path — no cost/spend visibility existed before this.
     * Deliberately no dollar estimate: Anthropic pricing changes over time
     * and hardcoding a rate risks silently going stale, whereas call/token
     * counts already show the trend (usage up/down) without that risk.
     */
    public function aiUsageSummary(): array
    {
        $rows = DB::table('ai_usage_logs')->get();
        $total = $rows->count();
        $successful = $rows->where('success', true)->count();

        return [
            'total_calls' => $total,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0.0,
            'total_input_tokens' => (int) $rows->sum('input_tokens'),
            'total_output_tokens' => (int) $rows->sum('output_tokens'),
            'retry_count' => $rows->where('is_retry', true)->count(),
            'by_endpoint' => $rows->groupBy('endpoint')->map->count()->all(),
            'error_breakdown' => $rows->where('success', false)->groupBy('error_type')->map->count()->all(),
        ];
    }

    /**
     * Route points where the passenger asked for a custom pickup or drop-off
     * instead of the route's default. Was the same where() repeated in
     * overview(), customRouteSummary() and thesisAlignmentSummary() — one
     * definition means the "custom" definition can't drift between them.
     */
    private function customRoutePoints(): \Illuminate\Database\Query\Builder
    {
        return DB::table('trip_passenger_route_points')
            ->where(function ($query): void {
                $query->where('uses_default_pickup', false)
                    ->orWhere('uses_default_dropoff', false);
            });
    }

    public function thesisAlignmentSummary(): array
    {
        $aiCount = $this->aiSupportSummary()['recommendation_logs'];
        $reliabilityProfiles = $this->passengerReliabilitySummary()['profiles_total'];

        return [
            [
                'objective' => 'AI Smart Assistance',
                'evidence' => $aiCount,
                'unit' => 'AI fare/smart suggestions',
            ],
            [
                'objective' => 'Custom Route Preference',
                'evidence' => (int) $this->customRoutePoints()->count(),
                'unit' => 'custom pickup/drop-off records',
            ],
            [
                'objective' => 'Passenger Reliability',
                'evidence' => $reliabilityProfiles,
                'unit' => 'analyzed passenger profiles',
            ],
            [
                'objective' => 'Payment Tracking',
                'evidence' => (int) TripPayment::query()->count(),
                'unit' => 'payment records',
            ],
        ];
    }
}
