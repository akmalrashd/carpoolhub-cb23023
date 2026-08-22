<?php

namespace App\Http\Controllers;

use App\Http\Requests\Explore\RemoveTripParticipantRequest;
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

    public function respond(RespondTripJoinRequest $request, TripJoinRequest $joinRequest): RedirectResponse|JsonResponse
    {
        try {
            $joinRequest = $this->tripJoinRequestService->respond(
                $request->user(),
                $joinRequest,
                $request->validated('action'),
                $request->validated('response_note')
            );
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Request could not be updated.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        $statusText = $request->validated('action') === 'approve' ? 'approved' : 'rejected';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Join request {$statusText}.",
                'id' => $joinRequest->id,
                'status' => $joinRequest->status,
                'action' => $request->validated('action'),
            ]);
        }

        return back()->with('status', "Join request {$statusText}.");
    }

    public function remove(RemoveTripParticipantRequest $request, TripJoinRequest $joinRequest): RedirectResponse|JsonResponse
    {
        try {
            $participant = $this->tripJoinRequestService->removeParticipant(
                $request->user(),
                $joinRequest,
                $request->validated('reason')
            );
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Passenger could not be removed.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Passenger removed from trip.',
                'id' => $joinRequest->id,
                'attendance_status' => $participant->attendance_status,
                'attendance_note' => $participant->attendance_note,
            ]);
        }

        return back()->with('status', 'Passenger removed from trip.');
    }

    public function markAbsent(Request $request, TripJoinRequest $joinRequest): RedirectResponse|JsonResponse
    {
        try {
            $participant = $this->tripJoinRequestService->markAbsent($request->user(), $joinRequest);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Passenger could not be marked absent.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Passenger marked absent.',
                'id' => $joinRequest->id,
                'attendance_status' => $participant->attendance_status,
            ]);
        }

        return back()->with('status', 'Passenger marked absent.');
    }

    /**
     * A passenger cancelling their own request/seat — pending or approved.
     * Distinct from ExploreController::cancelRequest (same underlying service
     * method, older pending-only route never wired up to any button); this
     * one backs the "My Request" popup on the Trips page and is JSON-first.
     */
    public function cancel(Request $request, TripJoinRequest $joinRequest): RedirectResponse|JsonResponse
    {
        try {
            $this->tripJoinRequestService->cancelRequest($request->user(), $joinRequest);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Request could not be cancelled.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Request cancelled.',
                'id' => $joinRequest->id,
                'status' => 'cancelled',
            ]);
        }

        return back()->with('status', 'Request cancelled.');
    }

    /**
     * Self-leave for a pre-selected participant (no TripJoinRequest exists for
     * them at all) — the "My Request" popup's counterpart to cancel() above.
     */
    public function leave(Request $request, Trip $trip): RedirectResponse|JsonResponse
    {
        try {
            $this->tripJoinRequestService->leaveTrip($request->user(), $trip);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Request could not be cancelled.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You have left the trip.',
            ]);
        }

        return back()->with('status', 'You have left the trip.');
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
