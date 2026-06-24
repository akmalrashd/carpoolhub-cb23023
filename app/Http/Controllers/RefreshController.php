<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripPayment;
use App\Services\Ai\PassengerRiskScoringService;
use App\Services\NotificationService;
use App\Services\PassengerReliabilityService;
use App\Services\PaymentService;
use App\Services\TripJoinRequestService;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly TripJoinRequestService $tripJoinRequestService,
        private readonly PassengerReliabilityService $passengerReliabilityService,
        private readonly PassengerRiskScoringService $passengerRiskScoringService,
        private readonly TripService $tripService,
        private readonly PaymentService $paymentService,
    ) {
    }

    public function notificationsLatest(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->notificationService->recentForUser($user, 6);
        $unreadCount = $this->notificationService->unreadCount($user);

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications->map(fn ($item) => [
                'id'         => (int) $item->id,
                'type'       => (string) ($item->type ?? 'system'),
                'title'      => (string) $item->title,
                'message'    => (string) $item->message,
                'is_read'    => (bool) $item->is_read,
                'time_ago'   => (string) ($item->created_at?->diffForHumans() ?? ''),
                'target_url' => (string) $item->target_url,
                'open_url'   => route('notifications.open', $item),
            ])->values()->all(),
        ]);
    }

    public function tripRequests(Request $request, Trip $trip): JsonResponse
    {
        $trip->load(['savedRoute', 'driver', 'participants.user', 'payments', 'returnTrip.payments', 'returnTrip']);
        $requests = $this->tripJoinRequestService->listForTrip($request->user(), $trip);
        $reliabilityMap = $this->passengerReliabilityService->buildForUsers(
            $requests->getCollection()->pluck('user_id')->unique()->values()
        );
        $aiRiskMap = $requests->getCollection()
            ->mapWithKeys(fn ($joinRequest) => [
                $joinRequest->user_id => $this->passengerRiskScoringService->scoreUserForTrip(
                    $joinRequest->user,
                    $trip,
                    $trip->driver
                ),
            ])
            ->all();

        $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
        $availableSeats = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : null;

        return response()->json([
            'trip' => [
                'status_text' => ucfirst((string) $trip->status),
                'is_open_for_request' => (bool) $trip->is_open_for_request,
                'visibility' => (string) $trip->visibility,
                'available_seats_text' => $availableSeats !== null
                    ? ($availableSeats . ' available / ' . (int) $trip->seat_limit)
                    : 'Open',
            ],
            'requests_html' => view('trips.partials.requests-list', [
                'requests' => $requests,
                'reliabilityMap' => $reliabilityMap,
                'aiRiskMap' => $aiRiskMap,
                'trip' => $trip,
            ])->render(),
            'pagination_html' => $requests->links()->toHtml(),
        ]);
    }

    public function tripStatus(Request $request, Trip $trip): JsonResponse
    {
        $this->tripService->ensureTripOwner($request->user(), $trip);
        $trip->load(['parentTrip']);

        $displayTrip = ($trip->is_return_trip && $trip->parentTrip)
            ? $trip->parentTrip
            : $trip;

        $displayTrip->load([
            'participants.user',
            'payments',
            'returnTrip.payments',
        ]);

        $passengers = $displayTrip->participants
            ->filter(fn ($participant) => ! $participant->is_driver)
            ->values();

        $rollupPayments = $displayTrip->payments->values();
        if ($displayTrip->returnTrip) {
            $rollupPayments = $rollupPayments->merge($displayTrip->returnTrip->payments);
        }

        $sumAmount = fn (string $status): float => (float) $rollupPayments
            ->where('payment_status', $status)
            ->sum('amount_due');

        $countByStatus = fn (string $status): int => (int) $rollupPayments
            ->where('payment_status', $status)
            ->count();

        $rollups = [
            'unpaid' => [
                'count' => $countByStatus('unpaid'),
                'amount' => $sumAmount('unpaid'),
            ],
            'pending_confirmation' => [
                'count' => $countByStatus('pending_confirmation'),
                'amount' => $sumAmount('pending_confirmation'),
            ],
            'paid' => [
                'count' => $countByStatus('paid'),
                'amount' => $sumAmount('paid'),
            ],
            'total' => [
                'count' => (int) $rollupPayments->count(),
                'amount' => (float) $rollupPayments->sum('amount_due'),
            ],
        ];

        return response()->json([
            'status_text' => ucfirst((string) $displayTrip->status),
            'passenger_count' => (int) $passengers->count(),
            'passengers' => $passengers->map(fn ($participant) => [
                'name' => (string) ($participant->user?->name ?? '-'),
                'email' => (string) ($participant->user?->email ?? '-'),
                'initial' => strtoupper(substr((string) ($participant->user?->name ?? 'P'), 0, 1)),
            ])->values()->all(),
            'rollups' => $rollups,
        ]);
    }

    public function paymentsSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = (string) $user->role;
        $canReviewQueue = in_array($role, ['admin', 'driver'], true);

        $tripIds = null;
        if ($request->filled('trip_id')) {
            $trip = Trip::query()->with('parentTrip', 'returnTrip')->findOrFail((int) $request->query('trip_id'));
            $this->tripService->ensureTripAccessible($user, $trip);

            $baseTrip = ($trip->is_return_trip && $trip->parentTrip) ? $trip->parentTrip : $trip;
            $tripIds = [$baseTrip->id];
            if ($baseTrip->returnTrip) {
                $tripIds[] = $baseTrip->returnTrip->id;
            }
        }

        $summary = $this->paymentService->summarizeForUser($user, $tripIds);
        $passengerDebtSummary = $canReviewQueue
            ? $this->paymentService->summarizeOutstandingByPassenger($user, $tripIds)
            : null;

        $myRecordCount = TripPayment::query()
            ->where('user_id', $user->id)
            ->whereHas('trip', fn ($query) => $query
                                ->whereNotIn('status', ['draft', 'cancelled'])
                ->where(function ($subQuery): void {
                    $subQuery->whereNull('parent_trip_id')->orWhere('is_return_trip', false);
                })
            )
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds))
            ->count();

        $queueCount = $canReviewQueue
            ? TripPayment::query()
                ->when(
                    $role === 'admin',
                    fn ($query) => $query,
                    fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('driver_id', $user->id))
                        ->where('user_id', '!=', $user->id)
                )
                ->whereHas('trip', fn ($query) => $query
                                        ->whereNotIn('status', ['draft', 'cancelled'])
                    ->where(function ($subQuery): void {
                        $subQuery->whereNull('parent_trip_id')->orWhere('is_return_trip', false);
                    })
                )
                ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds))
                ->count()
            : 0;

        return response()->json([
            'summary' => $summary,
            'passenger_debt_summary' => $passengerDebtSummary,
            'tool_counts' => [
                'my_records' => (int) $myRecordCount,
                'queue_records' => (int) $queueCount,
                'unpaid_amount' => (float) ($summary['my']['unpaid']['amount'] ?? $summary['driver']['unpaid']['amount'] ?? 0),
                'pending_amount' => (float) ($summary['my']['pending_confirmation']['amount'] ?? $summary['driver']['pending_confirmation']['amount'] ?? 0),
            ],
        ]);
    }
}
