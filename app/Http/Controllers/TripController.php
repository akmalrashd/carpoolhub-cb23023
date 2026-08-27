<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Models\SavedRoute;
use App\Models\Trip;
use App\Models\User;
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
        $this->tripService->syncLifecycleStatuses();
        $tripStatusCounts = $this->tripService->statusCountsForUser($request->user(), $filters);
        $trips = $this->tripService->paginateForUser($request->user(), 10, $filters);

        return view('trips.index', [
            'trips' => $trips,
            'filters' => $filters,
            'tripStatusCounts' => $tripStatusCounts,
            'initialLoad' => false,
        ]);
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
        $this->ensureCanEditOrDelete($request);
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
        $this->ensureCanEditOrDelete($request);

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
        $this->ensureCanEditOrDelete($request);

        $this->tripService->delete($request->user(), $trip);

        return redirect()
            ->route('trips.index')
            ->with('status', 'Trip deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:trips,id',
        ]);

        $user = $request->user();
        $deletedCount = 0;

        // One query for the whole batch instead of a Trip::find() per id — the
        // ownership check stays per-trip so a mix of owned/not-owned ids still
        // deletes the owned ones and silently skips the rest, same as before.
        $trips = Trip::query()->whereIn('id', $request->input('ids', []))->get();

        foreach ($trips as $trip) {
            if ($user->role === 'admin' || (int) $user->id === (int) $trip->driver_id) {
                $this->tripService->delete($user, $trip);
                $deletedCount++;
            }
        }

        return redirect()
            ->route('trips.index')
            ->with('status', "{$deletedCount} trip(s) deleted successfully.");
    }

    private function ensureCanManage(Request $request): void
    {
        $user = $request->user();

        abort_unless($user->role === 'driver', 403);
        $this->ensureDriverIsApprovedAndCurrent($user);
    }

    /**
     * Editing/deleting an existing trip is oversight, not authorship — an
     * admin can manage any trip via ensureTripOwner()/TripService below, same
     * as the trip's own driver. Creating a brand-new trip stays driver-only
     * (ensureCanManage above): admin has no vehicle/license to drive it with.
     */
    private function ensureCanEditOrDelete(Request $request): void
    {
        $user = $request->user();

        abort_unless(in_array($user->role, ['driver', 'admin'], true), 403);

        if ($user->role === 'driver') {
            $this->ensureDriverIsApprovedAndCurrent($user);
        }
    }

    private function ensureDriverIsApprovedAndCurrent(User $user): void
    {
        if ($user->driver_verification_status !== 'approved') {
            abort(403, 'Your driver account is not approved to manage trips yet.');
        }

        if ($user->driving_license_expiry && $user->driving_license_expiry->isPast()) {
            abort(403, 'Your driving license has expired. Please update it in Settings before creating or editing trips.');
        }
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
