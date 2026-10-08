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
     * Returns only the average and the number of ratings, never the
     * individual rows. This is enforced by the code rather than just hidden in
     * the interface, because the query below never reads driver_ratings at
     * all. There is simply no path by which a driver, or anyone
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
