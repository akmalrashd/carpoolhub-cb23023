<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavedRoute\StoreSavedRouteRequest;
use App\Http\Requests\SavedRoute\UpdateSavedRouteRequest;
use App\Models\SavedRoute;
use App\Services\SavedRouteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedRouteController extends Controller
{
    public function __construct(private readonly SavedRouteService $savedRouteService)
    {
    }

    public function index(Request $request): View
    {
        if (! $request->header('HX-Request')) {
            return view('saved-routes.index', [
                'savedRoutes' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                'initialLoad' => true,
            ]);
        }

        $savedRoutes = $this->savedRouteService->paginateForUser($request->user());

        return view('saved-routes.index', compact('savedRoutes'))->with('initialLoad', false);
    }

    public function create(Request $request): View
    {
        $selectableParticipants = $this->savedRouteService->selectablePassengersFor($request->user());

        return view('saved-routes.create', compact('selectableParticipants'));
    }

    public function store(StoreSavedRouteRequest $request): RedirectResponse
    {
        $this->savedRouteService->create(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('saved-routes.index')
            ->with('status', 'Saved route created.');
    }

    public function edit(Request $request, SavedRoute $savedRoute): View
    {
        $this->authorizeOwner($request, $savedRoute);
        $savedRoute->load('passengerStops.user');
        $selectableParticipants = $this->savedRouteService->selectablePassengersFor($request->user());

        return view('saved-routes.edit', compact('savedRoute', 'selectableParticipants'));
    }

    public function update(UpdateSavedRouteRequest $request, SavedRoute $savedRoute): RedirectResponse
    {
        $this->authorizeOwner($request, $savedRoute);

        $this->savedRouteService->update($savedRoute, $request->validated());

        return redirect()
            ->route('saved-routes.index')
            ->with('status', 'Saved route updated.');
    }

    public function toggleStatus(Request $request, SavedRoute $savedRoute): RedirectResponse
    {
        $this->authorizeOwner($request, $savedRoute);

        $this->savedRouteService->toggleActive($savedRoute);

        return redirect()
            ->route('saved-routes.index')
            ->with('status', 'Saved route status updated.');
    }

    public function destroy(Request $request, SavedRoute $savedRoute): RedirectResponse
    {
        $this->authorizeOwner($request, $savedRoute);

        $this->savedRouteService->delete($savedRoute);

        return redirect()
            ->route('saved-routes.index')
            ->with('status', 'Saved route and related trips deleted.');
    }

    private function authorizeOwner(Request $request, SavedRoute $savedRoute): void
    {
        abort_if($savedRoute->user_id !== $request->user()->id, 403);
    }
}
