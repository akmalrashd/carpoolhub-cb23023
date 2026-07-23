<?php

namespace App\Http\Controllers;

use App\Models\SavedRoute;
use App\Models\Trip;
use App\Models\TripJoinRequest;
use App\Models\TripPayment;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $user->role;

        $activeUnpaidCount = (int) TripPayment::query()
            ->where('user_id', $user->id)
            ->where('payment_status', 'unpaid')
            ->whereHas('trip', fn ($tripQuery) => $tripQuery->where('status', '!=', 'draft'))
            ->count();

        $activeOutstandingAmount = (float) TripPayment::query()
            ->where('user_id', $user->id)
            ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
            ->whereHas('trip', fn ($tripQuery) => $tripQuery->where('status', '!=', 'draft'))
            ->sum('amount_due');

        $now = now();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $tripCounts = Trip::query()
                        ->where(function ($query) use ($user): void {
                $query->where('driver_id', $user->id)
                    ->orWhereHas('participants', fn ($q) => $q->where('user_id', $user->id));
            })
            ->selectRaw('
                COUNT(*) as total_trips,
                SUM(trip_datetime BETWEEN ? AND ?) as trips_this_week,
                SUM(trip_datetime BETWEEN ? AND ?) as trips_this_month
            ', [$weekStart, $weekEnd, $monthStart, $monthEnd])
            ->first();

        $stats = [
            'total_trips' => (int) ($tripCounts->total_trips ?? 0),
            'trips_this_week' => (int) ($tripCounts->trips_this_week ?? 0),
            'trips_this_month' => (int) ($tripCounts->trips_this_month ?? 0),
            'unpaid_count' => $activeUnpaidCount,
            'unpaid_amount' => $activeOutstandingAmount,
            'saved_routes' => SavedRoute::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->count(),
            'unread_notifications' => UserNotification::query()
                ->where('user_id', $user->id)
                ->where('is_read', false)
                ->count(),
        ];

        $stats['total_earnings'] = 'RM ' . number_format((float) TripPayment::query()
            ->where('payment_status', 'paid')
            ->whereHas('trip', fn ($query) => $query
                ->where('driver_id', $user->id)
                ->whereBetween('trip_datetime', [now()->startOfMonth(), now()->endOfMonth()]))
            ->sum('amount_due'), 2);

        $stats['pending_requests'] = (int) TripJoinRequest::query()
            ->where('status', 'pending')
            ->whereHas('trip', fn ($query) => $query->where('driver_id', $user->id))
            ->count();

        $upcomingCreatedTrips = Trip::query()
            ->with(['savedRoute', 'participants'])
                        ->where('driver_id', $user->id)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNotNull('trip_datetime')
            ->where('trip_datetime', '>=', now())
            ->orderBy('trip_datetime')
            ->limit(3)
            ->get();

        $upcomingJoinedTrips = Trip::query()
            ->with(['savedRoute', 'participants'])
                        ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNotNull('trip_datetime')
            ->where('trip_datetime', '>=', now())
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('is_driver', false);
            })
            ->orderBy('trip_datetime')
            ->limit(3)
            ->get();

        $upcomingCreatedTrip = $upcomingCreatedTrips->first();
        $upcomingJoinedTrip = $upcomingJoinedTrips->first();

        $pendingJoinRequests = $stats['pending_requests'];

        $driverReviewQueue = TripPayment::query()
            ->with(['trip.savedRoute', 'user' => fn ($q) => $q->withoutHeavyMedia()])
            ->where('payment_status', 'pending_confirmation')
            ->whereHas('trip', fn ($query) => $query->where('driver_id', $user->id))
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $publicExploreTrips = Trip::query()
            ->with(['savedRoute', 'participants', 'driver' => fn ($q) => $q->withoutHeavyMedia()])
                        ->whereNull('parent_trip_id')
            ->where('visibility', 'public')
            ->where('is_open_for_request', true)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('trip_datetime', '>=', now())
            ->where('driver_id', '!=', $user->id)
            ->whereRaw(
                '(seat_limit IS NULL OR seat_limit > (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.is_driver = 0))'
            )
            ->orderBy('trip_datetime')
            ->limit(10)
            ->get();

        return view('home', compact(
            'role',
            'stats',
            'upcomingCreatedTrips',
            'upcomingJoinedTrips',
            'upcomingCreatedTrip',
            'upcomingJoinedTrip',
            'pendingJoinRequests',
            'driverReviewQueue',
            'publicExploreTrips'
        ));
    }
}
