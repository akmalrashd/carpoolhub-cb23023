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
        $aiRiskMap = $requests->getCollection()
            ->mapWithKeys(fn (TripJoinRequest $joinRequest) => [
                $joinRequest->user_id => $this->passengerRiskScoringService->scoreUserForTrip(
                    $joinRequest->user,
                    $trip,
                    $trip->driver
                ),
            ])
            ->all();

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

    public function toggleOpen(ToggleTripRequestOpenState $request, Trip $trip): RedirectResponse
    {
        try {
            $this->tripJoinRequestService->setOpenState(
                $request->user(),
                $trip,
                (bool) $request->validated('is_open_for_request')
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Public join setting updated.');
    }
}
