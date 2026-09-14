<?php

namespace App\Http\Controllers;

use App\Http\Requests\DriverRating\StoreDriverRatingRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\DriverRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class DriverRatingController extends Controller
{
    public function __construct(
        private readonly DriverRatingService $ratingService,
    ) {}

    public function store(StoreDriverRatingRequest $request, Trip $trip): RedirectResponse|JsonResponse
    {
        try {
            $this->ratingService->submitRating(
                $request->user(),
                $trip,
                (int) $request->validated('stars')
            );
        } catch (ValidationException $exception) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Thanks for rating your driver!',
            ]);
        }

        return back()->with('status', 'Thanks for rating your driver!');
    }

    /**
     * Aggregate only — average + count, never the individual rows. This is
     * structural, not just a UI choice: the query below never selects from
     * driver_ratings, so there's no code path by which a driver (or anyone
     * else) can learn which passenger gave which score.
     */
    public function show(User $user): JsonResponse
    {
        $profile = $user->ratingProfile;

        return response()->json([
            'user_id' => $user->id,
            'rating_average' => $profile ? (float) $profile->rating_average : null,
            'rating_count' => $profile->rating_count ?? 0,
        ]);
    }
}
