<?php

namespace App\Http\Controllers;

use App\Http\Requests\Explore\StoreTripJoinRequest;
use App\Models\Connection;
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

    public function show(Request $request, Trip $trip): View
    {
        $this->tripService->syncLifecycleStatuses();

        abort_if($trip->is_return_trip, 404);
        abort_unless($trip->visibility === 'public', 404);
        abort_unless(
            Trip::query()
                ->whereKey($trip->id)
                                ->exists(),
            404
        );

        $trip->load([
            'savedRoute',
            'driver',
            'participants.user',
            'joinRequests' => fn ($joinQuery) => $joinQuery->with(['user', 'routePoint'])->latest('id'),
            'returnTrip',
        ]);

        $myRequest = $trip->joinRequests->firstWhere('user_id', $request->user()->id);
        $isJoined = $trip->participants->contains(fn ($participant) => (int) $participant->user_id === (int) $request->user()->id);
        $hasApprovedJoinAccess = $isJoined || ((string) ($myRequest?->status ?? '') === 'approved');
        $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
        $availableSeats = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : null;
        $viewerId = (int) $request->user()->id;
        $driverId = (int) $trip->driver_id;
        $isFriendOrSelf = $viewerId === $driverId || Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($viewerId, $driverId): void {
                $query->where(function ($pair) use ($viewerId, $driverId): void {
                    $pair->where('requester_id', $viewerId)
                        ->where('receiver_id', $driverId);
                })->orWhere(function ($pair) use ($viewerId, $driverId): void {
                    $pair->where('requester_id', $driverId)
                        ->where('receiver_id', $viewerId);
                });
            })
            ->exists();

        $phoneVisibility = (string) ($trip->driver?->phone_visible ?: 'visible_friend');
        $emailVisibility = (string) ($trip->driver?->email_visible ?: 'visible_friend');

        $canViewDriverWhatsapp = $hasApprovedJoinAccess || match ($phoneVisibility) {
            'visible_public' => true,
            'visible_friend' => $isFriendOrSelf,
            default => false,
        };
        $canViewDriverEmail = $hasApprovedJoinAccess || match ($emailVisibility) {
            'visible_public' => true,
            'visible_friend' => $isFriendOrSelf,
            default => false,
        };
        $aiRecommendation = $this->aiDecisionSupportService->recommendTrips($request->user(), collect([$trip]))->first()['score'] ?? null;

        return view('explore.show', compact(
            'trip',
            'myRequest',
            'isJoined',
            'takenSeats',
            'availableSeats',
            'canViewDriverWhatsapp',
            'canViewDriverEmail',
            'aiRecommendation'
        ));
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
