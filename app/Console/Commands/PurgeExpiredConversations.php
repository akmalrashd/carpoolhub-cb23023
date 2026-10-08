<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\SystemSetting;
use Illuminate\Console\Command;

/**
 * Two purge paths, both hard-delete (no soft-delete anywhere in this app,
 * matching Trip/TripCancellationLog):
 *
 *  1. Conversations that ChatService::scheduleClosure() closed on purpose,
 *     because the trip was cancelled or lost all its passengers, once their
 *     grace period in scheduled_purge_at has passed.
 *  2. Conversations that were never explicitly closed because the trip
 *     simply happened and finished. There is no "trip completed" event in
 *     this app, since the status is worked out when it is asked for in
 *     TripService::syncLifecycleStatuses, so this branch handles the normal
 *     case by reading trip_datetime_snapshot directly.
 */
class PurgeExpiredConversations extends Command
{
    protected $signature = 'chats:purge-expired';

    protected $description = 'Delete trip chats whose grace period has passed';

    public function handle(): int
    {
        $explicitlyClosed = Conversation::query()
            ->where('is_circle', false)
            ->whereNotNull('scheduled_purge_at')
            ->where('scheduled_purge_at', '<=', now())
            ->delete();

        $retentionDays = (int) (SystemSetting::get('chat_retention_days_after') ?? 3);

        // A circle routinely sits with a stale trip_datetime_snapshot between
        // uses, and that is its normal state rather than an edge case, so it
        // must never be caught by this sweep. PruneCircleMessages handles a
        // circle instead, trimming old messages rather than the
        // conversation itself.
        $naturallyExpired = Conversation::query()
            ->where('is_circle', false)
            ->whereNull('scheduled_purge_at')
            ->whereNotNull('trip_datetime_snapshot')
            ->where('trip_datetime_snapshot', '<=', now()->subDays($retentionDays))
            ->delete();

        $this->info("Purged {$explicitlyClosed} explicitly-closed and {$naturallyExpired} naturally-expired conversation(s).");

        return self::SUCCESS;
    }
}
