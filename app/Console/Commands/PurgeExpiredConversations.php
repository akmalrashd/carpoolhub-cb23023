<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\SystemSetting;
use Illuminate\Console\Command;

/**
 * Two purge paths, both hard-delete (no soft-delete anywhere in this app,
 * matching Trip/TripCancellationLog):
 *
 *  1. Conversations explicitly closed by ChatService::scheduleClosure() —
 *     the trip was cancelled or emptied out — once their grace period
 *     (scheduled_purge_at) has passed.
 *  2. Conversations that were never explicitly closed because the trip
 *     simply ran its course — there's no "trip completed" event in this app
 *     (status is computed on demand, see TripService::syncLifecycleStatuses),
 *     so this covers the normal case directly off trip_datetime_snapshot.
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
        // uses (that's its normal steady state, not an edge case) — it must
        // never be swept up here. See PruneCircleMessages for its own
        // age-based cleanup, which prunes old messages instead of the
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
