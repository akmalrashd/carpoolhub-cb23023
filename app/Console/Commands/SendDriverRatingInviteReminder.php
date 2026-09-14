<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\SystemSetting;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\ChatService;
use App\Services\DriverRatingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Scheduled daily (see bootstrap/app.php). Two jobs in one pass, both
 * driven off the same "who still needs to rate" window (driver_rating_
 * window_days, default 14):
 *
 *  1. A one-time Hexa chat message posted into a newly-completed public
 *     trip's conversation — de-duped by checking for an existing
 *     Message::TYPE_RATING_INVITE row, so it's never posted twice.
 *  2. A daily-refreshing in-app + Telegram reminder, using the same
 *     delete-all-mine-then-recreate pattern as SendUnreadChatReminder
 *     (not PruneOldNotifications' cooldown-window pattern) — a single
 *     star-tap has no reason to be nagged about on a cooldown the way a
 *     payment deadline is, and the product spec explicitly wants the
 *     reminder to "disappear once rated," which this pattern gives for
 *     free rather than needing an extra "already rated, don't re-warn"
 *     check.
 *
 * Both stop entirely once a trip ages past the window — there's no
 * separate cleanup job, see the "who still needs to rate" query in
 * DriverRatingService, which a trip simply falls out of.
 */
class SendDriverRatingInviteReminder extends Command
{
    private const REMINDER_RELATED_TYPE = 'driver_rating_invite';

    protected $signature = 'ratings:invite-reminder';

    protected $description = 'Post a one-time Hexa "rate your driver" chat message on newly-completed public trips, and daily-refresh the in-app+Telegram reminder for anyone still inside the rating window';

    public function handle(DriverRatingService $ratingService, ChatService $chatService): int
    {
        $windowDays = (int) (SystemSetting::get('driver_rating_window_days') ?? 14);
        $now = Trip::now();
        $windowStart = $now->clone()->subDays($windowDays);

        $posted = $this->postChatInvites($ratingService, $chatService, $now, $windowStart);
        $reminded = $this->refreshNotifications($now, $windowStart);

        $this->info("Posted {$posted} chat invite(s), reminded {$reminded} user(s) about unrated trips.");

        return self::SUCCESS;
    }

    private function postChatInvites(DriverRatingService $ratingService, ChatService $chatService, \Carbon\Carbon $now, \Carbon\Carbon $windowStart): int
    {
        $completedInWindow = Trip::query()
            ->where('visibility', 'public')
            ->where('trip_datetime', '<=', $now)
            ->where('trip_datetime', '>=', $windowStart)
            ->get();

        $posted = 0;

        foreach ($completedInWindow as $trip) {
            $conversation = $trip->conversation;
            if (! $conversation) {
                continue;
            }

            $alreadyPosted = Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('type', Message::TYPE_RATING_INVITE)
                ->exists();
            if ($alreadyPosted) {
                continue;
            }

            if ($ratingService->unratedParticipantIdsForTrip($trip)->isEmpty()) {
                continue;
            }

            $chatService->postRatingInvite($trip, $conversation);
            $posted++;
        }

        return $posted;
    }

    private function refreshNotifications(\Carbon\Carbon $now, \Carbon\Carbon $windowStart): int
    {
        UserNotification::query()->where('related_type', self::REMINDER_RELATED_TYPE)->delete();

        $pendingRows = TripParticipant::query()
            ->where('attendance_status', 'joined')
            ->where('is_driver', false)
            ->whereHas('trip', fn ($query) => $query
                ->where('visibility', 'public')
                ->where('trip_datetime', '<=', $now)
                ->where('trip_datetime', '>=', $windowStart))
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('driver_ratings')
                    ->whereColumn('driver_ratings.trip_id', 'trip_participants.trip_id')
                    ->whereColumn('driver_ratings.rater_user_id', 'trip_participants.user_id');
            })
            ->get(['trip_id', 'user_id']);

        if ($pendingRows->isEmpty()) {
            return 0;
        }

        $activeUserIds = User::query()
            ->whereIn('id', $pendingRows->pluck('user_id')->unique())
            ->where('is_active', true)
            ->pluck('id');

        $reminded = 0;

        foreach ($pendingRows->groupBy('user_id') as $userId => $rows) {
            $userId = (int) $userId;
            if (! $activeUserIds->contains($userId)) {
                continue;
            }

            $this->remind($userId, $rows->pluck('trip_id')->unique()->values());
            $reminded++;
        }

        return $reminded;
    }

    private function remind(int $userId, \Illuminate\Support\Collection $tripIds): void
    {
        $count = $tripIds->count();
        $tripWord = $count === 1 ? 'trip' : 'trips';

        UserNotification::query()->create([
            'user_id' => $userId,
            'type' => 'rating',
            'title' => 'Rate Your Driver',
            'message' => "You have {$count} recent {$tripWord} waiting for your rating. It only takes a second!",
            'telegram_message' => "⭐ <b>Rate Your Driver</b>\n\nYou have {$count} recent {$tripWord} waiting for your rating. Tap below to rate.",
            'related_type' => self::REMINDER_RELATED_TYPE,
            // A single unrated trip deep-links straight into it; several
            // fall back to the trip list (see UserNotification::
            // resolveDriverRatingUrl()).
            'related_id' => $count === 1 ? $tripIds->first() : null,
            'is_read' => false,
        ]);
    }
}
