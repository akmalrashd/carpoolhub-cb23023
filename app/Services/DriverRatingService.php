<?php

namespace App\Services;

use App\Models\DriverRating;
use App\Models\DriverRatingProfile;
use App\Models\SystemSetting;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Passengers rate the driver of a public trip they actually rode on, once
 * it's completed — one direction only (drivers don't rate passengers; that
 * trust dimension is already covered, differently, by
 * PassengerRiskProfile/PassengerReliabilityService). Scoped to public trips
 * only: private/circle passengers are the driver's own Connections already,
 * so rating them adds little value and is easy to game between friends.
 */
class DriverRatingService
{
    private const REMINDER_RELATED_TYPE = 'driver_rating_invite';

    public function isEligibleToRate(User $passenger, Trip $trip): void
    {
        if ((int) $trip->driver_id === (int) $passenger->id) {
            throw ValidationException::withMessages(['stars' => 'You cannot rate your own trip.']);
        }

        if ($trip->visibility !== 'public') {
            throw ValidationException::withMessages(['stars' => 'Only public trips can be rated.']);
        }

        if ($trip->trip_datetime === null || $trip->trip_datetime->gt(Trip::now())) {
            throw ValidationException::withMessages(['stars' => 'This trip has not completed yet.']);
        }

        // Without this, the window is only a UI convention (the button just
        // stops rendering) — a direct POST past the window, or a stale
        // cached notification link, would otherwise still succeed server-
        // side. eligibleTripsToRate() applies the same bound for display;
        // this is the actual enforcement.
        $windowDays = (int) (SystemSetting::get('driver_rating_window_days') ?? 14);
        if ($trip->trip_datetime->lt(Trip::now()->clone()->subDays($windowDays))) {
            throw ValidationException::withMessages(['stars' => 'The rating window for this trip has closed.']);
        }

        $participant = TripParticipant::query()
            ->where('trip_id', $trip->id)
            ->where('user_id', $passenger->id)
            ->where('attendance_status', 'joined')
            ->where('is_driver', false)
            ->first();

        if (! $participant) {
            throw ValidationException::withMessages(['stars' => 'You were not a passenger on this trip.']);
        }

        if (DriverRating::query()->where('trip_id', $trip->id)->where('rater_user_id', $passenger->id)->exists()) {
            throw ValidationException::withMessages(['stars' => 'You have already rated this trip.']);
        }
    }

    public function submitRating(User $passenger, Trip $trip, int $stars): DriverRating
    {
        // Re-validated here too — the "eligible trips" list a caller fetched
        // to show the button may already be stale by the time they submit
        // (someone else's concurrent removal, the trip aging out, etc.).
        $this->isEligibleToRate($passenger, $trip);

        return DB::transaction(function () use ($passenger, $trip, $stars): DriverRating {
            $rating = DriverRating::query()->firstOrCreate(
                ['trip_id' => $trip->id, 'rater_user_id' => $passenger->id],
                ['driver_id' => $trip->driver_id, 'stars' => $stars]
            );

            if (! $rating->wasRecentlyCreated) {
                // Lost a race against a concurrent duplicate submit — the
                // unique index would reject a raw insert anyway; surface the
                // same message isEligibleToRate() already gives this case.
                throw ValidationException::withMessages(['stars' => 'You have already rated this trip.']);
            }

            DriverRatingProfile::query()->firstOrCreate(['user_id' => $trip->driver_id]);

            // One atomic statement — MySQL's row lock on the UPDATE
            // serializes concurrent raters of the same driver correctly
            // without a separate lockForUpdate() SELECT. rating_average is
            // recomputed from the exact integer counters every time, not
            // incremented as a running float, so it can never drift.
            DB::update(
                'UPDATE driver_rating_profiles
                 SET rating_count = rating_count + 1,
                     rating_sum = rating_sum + ?,
                     rating_average = ROUND((rating_sum + ?) / (rating_count + 1), 2),
                     last_rated_at = ?
                 WHERE user_id = ?',
                [$stars, $stars, now(), $trip->driver_id]
            );

            // Same-session cleanliness — don't make them wait for tomorrow's
            // cron sweep to see their own reminder disappear.
            UserNotification::query()
                ->where('user_id', $passenger->id)
                ->where('related_type', self::REMINDER_RELATED_TYPE)
                ->where(function ($query) use ($trip): void {
                    $query->whereNull('related_id')->orWhere('related_id', $trip->id);
                })
                ->delete();

            return $rating;
        });
    }

    /**
     * Cold path — a real SUM()/COUNT() rescan. Only called when
     * driver_ratings rows are removed out-of-band (a cancelled trip's
     * cascade delete — see TripService::delete()), never on the per-rating
     * hot path above.
     */
    public function recomputeForDriver(int $driverId): void
    {
        $aggregate = DriverRating::query()
            ->where('driver_id', $driverId)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(stars), 0) as total')
            ->first();

        DriverRatingProfile::query()->updateOrCreate(
            ['user_id' => $driverId],
            [
                'rating_count' => (int) $aggregate->cnt,
                'rating_sum' => (int) $aggregate->total,
                'rating_average' => $aggregate->cnt > 0 ? round($aggregate->total / $aggregate->cnt, 2) : 0,
            ]
        );
    }

    /**
     * Trips this passenger can currently rate — drives the trips-list/Trip
     * Details button and the Home banner count. Always re-derived live,
     * never cached, so a removal/cancellation/rating-elsewhere is reflected
     * immediately.
     *
     * @return \Illuminate\Support\Collection<int, Trip>
     */
    public function eligibleTripsToRate(User $passenger): Collection
    {
        $windowDays = (int) (SystemSetting::get('driver_rating_window_days') ?? 14);
        $ratedTripIds = DriverRating::ratedTripIdsFor($passenger);

        return Trip::query()
            ->where('visibility', 'public')
            ->where('trip_datetime', '<=', Trip::now())
            ->where('trip_datetime', '>=', Trip::now()->clone()->subDays($windowDays))
            ->where('driver_id', '!=', $passenger->id)
            ->whereHas('participants', fn ($query) => $query
                ->where('user_id', $passenger->id)
                ->where('attendance_status', 'joined')
                ->where('is_driver', false))
            ->whereNotIn('id', $ratedTripIds)
            ->get();
    }

    /**
     * Joined non-driver participants who haven't rated this trip yet —
     * drives the scheduled command's one-time "post the chat invite" check.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function unratedParticipantIdsForTrip(Trip $trip): Collection
    {
        return TripParticipant::query()
            ->where('trip_id', $trip->id)
            ->where('attendance_status', 'joined')
            ->where('is_driver', false)
            ->whereNotIn('user_id', DriverRating::query()->where('trip_id', $trip->id)->pluck('rater_user_id'))
            ->pluck('user_id');
    }
}
