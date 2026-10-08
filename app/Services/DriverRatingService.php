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
 * Handles the star rating a passenger gives to a driver after a public trip.
 *
 * The rating only goes one way. Passengers rate drivers, but drivers do not
 * rate passengers, because passenger trustworthiness is already measured
 * separately by PassengerReliabilityService using payment and attendance
 * records.
 *
 * Only public trips can be rated. On a private trip the passengers are
 * already the driver's own saved Connections, so a rating there would mostly
 * be friends rating friends and would not help anyone choose a stranger.
 */
class DriverRatingService
{
    private const REMINDER_RELATED_TYPE = 'driver_rating_invite';

    /**
     * Throws if this passenger is not allowed to rate this trip. Every rule
     * that decides whether the Rate button should appear lives here, so the
     * interface and the server always agree.
     */
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

        // The rating window has to be checked here, not only in the view. If
        // it were only a view rule, hiding the button would be the only thing
        // stopping an old notification link or a hand made POST request from
        // still submitting a rating months later.
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

    /**
     * Saves one rating and updates the driver's running average.
     */
    public function submitRating(User $passenger, Trip $trip, int $stars): DriverRating
    {
        // Checked again on submit because the page that showed the Rate
        // button may have been open for a while. The driver could have
        // removed this passenger, or the trip could have passed the rating
        // window, since the button was drawn.
        $this->isEligibleToRate($passenger, $trip);

        return DB::transaction(function () use ($passenger, $trip, $stars): DriverRating {
            $rating = DriverRating::query()->firstOrCreate(
                ['trip_id' => $trip->id, 'rater_user_id' => $passenger->id],
                ['driver_id' => $trip->driver_id, 'stars' => $stars]
            );

            if (! $rating->wasRecentlyCreated) {
                // Someone double clicked and the other request won the race.
                // The unique index on (trip_id, rater_user_id) would block a
                // second row anyway, so answer with the same message the
                // eligibility check gives for an already rated trip.
                throw ValidationException::withMessages(['stars' => 'You have already rated this trip.']);
            }

            DriverRatingProfile::query()->firstOrCreate(['user_id' => $trip->driver_id]);

            // Doing the whole update in one SQL statement lets MySQL lock the
            // row for us, so two passengers rating the same driver at the same
            // moment cannot overwrite each other. The average is recalculated
            // from the stored count and sum instead of being nudged up and
            // down, which keeps it exact no matter how many ratings come in.
            DB::update(
                'UPDATE driver_rating_profiles
                 SET rating_count = rating_count + 1,
                     rating_sum = rating_sum + ?,
                     rating_average = ROUND((rating_sum + ?) / (rating_count + 1), 2),
                     last_rated_at = ?
                 WHERE user_id = ?',
                [$stars, $stars, now(), $trip->driver_id]
            );

            // Clear this passenger's own reminder right away so it disappears
            // the moment they rate, instead of lingering until the next day
            // when the scheduled command rebuilds the reminder list.
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
     * Rebuilds a driver's average by counting every rating row again.
     *
     * This is the slow path and it is only needed when ratings disappear
     * without going through submitRating(), which happens when a trip is
     * cancelled and the database deletes its ratings along with it.
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
     * Returns the trips this passenger is still able to rate.
     *
     * Used by the trips list, the Trip Details popup and the reminder banner
     * on the home page. The list is always queried fresh rather than cached,
     * so a cancelled trip or a rating made on another device shows up
     * straight away.
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
     * Lists the passengers on a trip who have not rated it yet.
     *
     * The scheduled reminder uses this to decide whether a trip still needs
     * its one time rating invite posted into the chat.
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
