<?php

namespace App\Http\Controllers;

use App\Http\Requests\Explore\RespondTripJoinRequest;
use App\Http\Requests\Explore\ToggleTripRequestOpenState;
use App\Models\Trip;
use App\Models\TripJoinRequest;
use App\Services\Ai\PassengerRiskScoringService;
use App\Services\PassengerReliabilityService;
use App\Services\TripJoinRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TripJoinRequestController extends Controller
{
    public function __construct(
        private readonly TripJoinRequestService $tripJoinRequestService,
        private readonly PassengerReliabilityService $passengerReliabilityService,
        private readonly PassengerRiskScoringService $passengerRiskScoringService,
    )
    {
    }

    public function index(Request $request, Trip $trip): View
    {
        $trip->load(['savedRoute', 'driver', 'participants.user', 'payments', 'returnTrip.payments', 'returnTrip']);
        $requests = $this->tripJoinRequestService->listForTrip($request->user(), $trip);
        $reliabilityMap = $this->passengerReliabilityService->buildForUsers(
            $requests->getCollection()->pluck('user_id')->unique()->values()
        );
        // Score all requesters at once: features batched, reliability reused.
        $aiRiskMap = $this->passengerRiskScoringService->scoreUsersForTrip(
            $requests->getCollection()->map(fn (TripJoinRequest $joinRequest) => $joinRequest->user)->filter(),
            $trip,
            $trip->driver,
            $reliabilityMap
        );

        return view('trips.requests', compact('trip', 'requests', 'reliabilityMap', 'aiRiskMap'));
    }

    public function respond(RespondTripJoinRequest $request, TripJoinRequest $joinRequest): RedirectResponse
    {
        try {
            $this->tripJoinRequestService->respond(
                $request->user(),
                $joinRequest,
                $request->validated('action'),
                $request->validated('response_note')
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $statusText = $request->validated('action') === 'approve' ? 'approved' : 'rejected';

        return back()->with('status', "Join request {$statusText}.");
    }

    public function toggleOpen(ToggleTripRequestOpenState $request, Trip $trip): RedirectResponse|JsonResponse
    {
        $isOpen = (bool) $request->validated('is_open_for_request');

        try {
            $this->tripJoinRequestService->setOpenState(
                $request->user(),
                $trip,
                $isOpen
            );
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Public join setting could not be updated.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Public join setting updated.',
                'is_open_for_request' => $isOpen,
                'open_state' => $isOpen ? 'Open' : 'Closed',
            ]);
        }

        return back()->with('status', 'Public join setting updated.');
    }
}
