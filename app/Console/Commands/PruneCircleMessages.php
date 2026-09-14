<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\SystemSetting;
use Illuminate\Console\Command;

/**
 * Circles never expire by age (see PurgeExpiredConversations' is_circle
 * guard) — only their old messages do, to bound storage for a group that
 * might be reused every few months indefinitely. Deletes per-conversation
 * rather than one global query so the existing (conversation_id, created_at)
 * composite index on messages is actually used.
 */
class PruneCircleMessages extends Command
{
    protected $signature = 'chats:prune-circle-messages';

    protected $description = 'Delete old messages from persistent driver circles, keeping the circle and its membership intact';

    public function handle(): int
    {
        $retentionDays = (int) (SystemSetting::get('chat_circle_message_retention_days') ?? 60);
        $cutoff = now()->subDays($retentionDays);

        $circleIds = Conversation::query()->where('is_circle', true)->pluck('id');

        $totalDeleted = 0;
        foreach ($circleIds as $circleId) {
            $totalDeleted += Message::query()
                ->where('conversation_id', $circleId)
                ->where('created_at', '<', $cutoff)
                ->delete();
        }

        $this->info("Pruned {$totalDeleted} message(s) older than {$retentionDays} days from {$circleIds->count()} circle(s).");

        return self::SUCCESS;
    }
}
