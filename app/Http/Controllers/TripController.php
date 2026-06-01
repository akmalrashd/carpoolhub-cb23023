<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Models\SavedRoute;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function __construct(private readonly TripService $tripService)
    {
    }

    public function index(Request $request): View
    {
        $this->tripService->syncLifecycleStatuses();
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'visibility' => ['nullable', 'in:public,private'],
            'status_filter' => ['nullable', 'in:all,upcoming,completed,draft,cancelled'],
            'trip_search' => ['nullable', 'string', 'max:100'],
        ]);

        $filters['status_filter'] = $filters['status_filter'] ?? 'all';
        $tripStatusCounts = $this->tripService->statusCountsForUser($request->user(), $filters);
        $trips = $this->tripService->paginateForUser($request->user(), 10, $filters);

        return view('trips.index', compact('trips', 'filters', 'tripStatusCounts'));
    }

    public function create(Request $request): View
    {
        $this->ensureCanManage($request);

        $savedRoutes = SavedRoute::query()
            ->with('passengerStops.user')
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get();

        $selectableParticipants = $this->tripService->getSelectableParticipants($request->user());

        return view('trips.create', compact('savedRoutes', 'selectableParticipants'));
    }

    public function store(StoreTripRequest $request): RedirectResponse
    {
        $this->ensureCanManage($request);

        try {
            $payload = $request->validated();
            $trip = $this->tripService->create($request->user(), $payload);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $message = ($payload['trip_type'] ?? 'one_way') === 'two_way'
            ? 'Two-way trips created with participants and fare split.'
            : 'Trip created with participants and fare split.';

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', $message);
    }

    public function show(Request $request, Trip $trip): View
    {
        $this->tripService->syncLifecycleStatuses();
        $this->tripService->ensureTripOwner($request->user(), $trip);

        $trip->load(['parentTrip']);

        $displayTrip = ($trip->is_return_trip && $trip->parentTrip)
            ? $trip->parentTrip
            : $trip;

        $displayTrip->load([
            'savedRoute',
            'driver',
            'participants.user',
            'payments.user',
            'passengerRoutePoints.user',
            'joinRequests.user',
            'returnTrip.savedRoute',
            'returnTrip.driver',
            'returnTrip.participants.user',
            'returnTrip.payments.user',
            'returnTrip.passengerRoutePoints.user',
        ]);

        $rollups = $this->buildGroupPaymentRollups($displayTrip);

        return view('trips.show', ['trip' => $displayTrip, 'rollups' => $rollups]);
    }

    public function edit(Request $request, Trip $trip): View
    {
        $this->ensureCanManage($request);
        $this->tripService->ensureTripOwner($request->user(), $trip);
        $trip->load('returnTrip');

        $savedRoutes = SavedRoute::query()
            ->with('passengerStops.user')
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get();

        $selectableParticipants = $this->tripService->getSelectableParticipants($request->user());
        $selectedParticipants = $trip->participants()
            ->where('user_id', '!=', $request->user()->id)
            ->pluck('user_id')
            ->all();

        return view('trips.edit', compact('trip', 'savedRoutes', 'selectableParticipants', 'selectedParticipants'));
    }

    public function update(UpdateTripRequest $request, Trip $trip): RedirectResponse
    {
        $this->ensureCanManage($request);

        try {
            $updatedTrip = $this->tripService->update($request->user(), $trip, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('trips.show', $updatedTrip)
            ->with('status', 'Trip updated and fare split recalculated.');
    }

    public function destroy(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureCanManage($request);

        $this->tripService->delete($request->user(), $trip);

        return redirect()
            ->route('trips.index')
            ->with('status', 'Trip deleted successfully.');
    }

    private function ensureCanManage(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['admin', 'driver'], true), 403);
    }

    private function buildGroupPaymentRollups(Trip $trip): array
    {
        $payments = $trip->payments->values();

        if ($trip->returnTrip) {
            $payments = $payments->merge($trip->returnTrip->payments);
        }

        $sumAmount = fn (string $status): float => (float) $payments
            ->where('payment_status', $status)
            ->sum('amount_due');

        $countByStatus = fn (string $status): int => (int) $payments
            ->where('payment_status', $status)
            ->count();

        return [
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
                'count' => (int) $payments->count(),
                'amount' => (float) $payments->sum('amount_due'),
            ],
        ];
    }

}
