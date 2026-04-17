<?php

namespace App\Http\Controllers;

use App\Models\SavedRoute;
use App\Models\ArchivedTripPayment;
use App\Models\Trip;
use App\Models\TripJoinRequest;
use App\Models\TripParticipant;
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
            ->whereHas('trip', fn ($tripQuery) => $tripQuery->activeOperational()->where('status', '!=', 'draft'))
            ->count();

        $archivedUnpaidCount = (int) ArchivedTripPayment::query()
            ->where('user_id', $user->id)
            ->where('payment_status', 'unpaid')
            ->whereHas('archivedTrip')
            ->count();

        $activeOutstandingAmount = (float) TripPayment::query()
            ->where('user_id', $user->id)
            ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
            ->whereHas('trip', fn ($tripQuery) => $tripQuery->activeOperational()->where('status', '!=', 'draft'))
            ->sum('amount_due');

        $archivedOutstandingAmount = (float) ArchivedTripPayment::query()
            ->where('user_id', $user->id)
            ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
            ->whereHas('archivedTrip')
            ->sum('amount_due');

        $stats = [
            'total_trips' => Trip::query()
                ->activeOperational()
                ->where(function ($query) use ($user): void {
                    $query->where('driver_id', $user->id)
                        ->orWhereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $user->id));
                })
                ->count(),
            'unpaid_count' => $activeUnpaidCount + $archivedUnpaidCount,
            'unpaid_amount' => $activeOutstandingAmount + $archivedOutstandingAmount,
            'saved_routes' => SavedRoute::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->count(),
            'unread_notifications' => UserNotification::query()
                ->where('user_id', $user->id)
                ->where('is_read', false)
                ->count(),
        ];

        $upcomingCreatedTrip = Trip::query()
            ->with('savedRoute')
            ->activeOperational()
            ->where('driver_id', $user->id)
            ->where('status', 'scheduled')
            ->whereNotNull('trip_datetime')
            ->where('trip_datetime', '>=', now())
            ->orderBy('trip_datetime')
            ->first();

        $upcomingJoinedTrip = Trip::query()
            ->with('savedRoute')
            ->activeOperational()
            ->where('status', 'scheduled')
            ->whereNotNull('trip_datetime')
            ->where('trip_datetime', '>=', now())
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('is_driver', false);
            })
            ->orderBy('trip_datetime')
            ->first();

        $pendingJoinRequests = TripJoinRequest::query()
            ->where('status', 'pending')
            ->whereHas('trip', fn ($query) => $query->activeOperational()->where('driver_id', $user->id))
            ->count();

        $joinedTripsCount = TripParticipant::query()
            ->where('user_id', $user->id)
            ->where('is_driver', false)
            ->whereHas('trip', fn ($query) => $query->activeOperational())
            ->count();

        $publicExploreTrips = Trip::query()
            ->with(['savedRoute', 'driver', 'participants'])
            ->activeOperational()
            ->whereNull('parent_trip_id')
            ->where('visibility', 'public')
            ->where('is_open_for_request', true)
            ->where('status', 'scheduled')
            ->where('trip_datetime', '>=', now())
            ->where('driver_id', '!=', $user->id)
            ->whereRaw(
                '(seat_limit IS NULL OR seat_limit > (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.is_driver = 0))'
            )
            ->orderByDesc('trip_datetime')
            ->limit(10)
            ->get();

        return view('home', compact(
            'role',
            'stats',
            'upcomingCreatedTrip',
            'upcomingJoinedTrip',
            'pendingJoinRequests',
            'joinedTripsCount',
            'publicExploreTrips'
        ));
    }
}
