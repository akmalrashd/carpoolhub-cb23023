<?php

namespace App\Http\Controllers;

use App\Http\Requests\Explore\StoreTripJoinRequest;
use App\Models\Trip;
use App\Models\TripJoinRequest;
use App\Services\Ai\AiDecisionSupportService;
use App\Services\TripJoinRequestService;
use App\Services\TripService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExploreController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly TripJoinRequestService $tripJoinRequestService,
        private readonly AiDecisionSupportService $aiDecisionSupportService,
    )
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'destination' => ['nullable', 'string', 'max:255'],
            'pickup' => ['nullable', 'string', 'max:255'],
            'driver' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'sort' => ['nullable', 'in:nearest,latest'],
            'timeframe' => ['nullable', 'in:today,tomorrow,weekend'],
            'seats' => ['nullable', 'in:1,2plus'],
            'fare_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'connections' => ['nullable', 'in:1'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'destination_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.5', 'max:200'],
        ]);

        if (! empty($filters['center_lat']) && ! empty($filters['center_lng']) && empty($filters['radius_km'])) {
            $filters['radius_km'] = 5;
        }

        $destination = trim((string) ($filters['destination'] ?? ''));
        if ($destination !== '') {
            $recent = collect($request->session()->get('explore_recent_destinations', []))
                ->prepend($destination)
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->take(8)
                ->values()
                ->all();
            $request->session()->put('explore_recent_destinations', $recent);
        }

        $trips = $this->tripService->paginateExplore($request->user(), 12, $filters);
        $rankedTrips = $this->aiDecisionSupportService->recommendTrips($request->user(), $trips->getCollection(), $filters);
        $trips->setCollection($rankedTrips->pluck('trip'));
        $aiRecommendationMap = $rankedTrips->mapWithKeys(fn (array $row) => [
            $row['trip']->id => $row['score'],
        ])->all();
        $recommendedTripIds = $rankedTrips->take(3)->map(fn (array $row) => (int) $row['trip']->id)->all();
        $suggestedDestinations = $this->tripService->exploreDestinationSuggestions();

        return view('explore.index', compact('trips', 'filters', 'suggestedDestinations', 'aiRecommendationMap', 'recommendedTripIds'));
    }

    public function search(Request $request): View
    {
        $prefill = trim((string) $request->query('destination', ''));
        $recentSearches = collect($request->session()->get('explore_recent_destinations', []))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();
        $suggestedDestinations = $this->tripService->exploreDestinationSuggestions(12);

        return view('explore.search', compact('prefill', 'recentSearches', 'suggestedDestinations'));
    }

    public function show(Trip $trip): RedirectResponse
    {
        return redirect()->route('explore.index', ['focus_trip' => $trip->id]);
    }

    public function requestJoin(StoreTripJoinRequest $request, Trip $trip): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validated();
            $this->tripJoinRequestService->submitRequest(
                $request->user(),
                $trip,
                $validated['request_note'] ?? null,
                $validated
            );
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Join request could not be submitted.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Join request submitted.',
                'status' => 'pending',
            ]);
        }

        return back()->with('status', 'Join request submitted.');
    }

    public function cancelRequest(Request $request, TripJoinRequest $joinRequest): RedirectResponse
    {
        try {
            $this->tripJoinRequestService->cancelRequest($request->user(), $joinRequest);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Join request cancelled.');
    }
}
