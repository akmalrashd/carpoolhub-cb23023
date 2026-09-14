<?php

namespace App\Console\Commands;

use App\Models\ConversationParticipant;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Scheduled daily (see bootstrap/app.php). Unlike this app's other
 * notification reminders (SendPendingPaymentApprovalReminder,
 * SendPaymentGraceDeadlineWarning), which use a cooldown window to avoid
 * re-notifying too often while still letting old reminders accumulate,
 * this one is meant to refresh every single day: every existing reminder
 * from this command is deleted up front, then a fresh one is created only
 * for whoever still has unread chats right now. That means a user never
 * has more than one of these at a time, and a chat that's since been read
 * simply stops getting a new row — no stale reminder lingers, and nothing
 * piles up in the notifications table.
 */
class SendUnreadChatReminder extends Command
{
    private const REMINDER_RELATED_TYPE = 'unread_chat_reminder';

    protected $signature = 'notifications:unread-chat-reminder';

    protected $description = "Daily-refreshed reminder for anyone with unread chat messages — replaces yesterday's instead of accumulating";

    public function handle(): int
    {
        UserNotification::query()->where('related_type', self::REMINDER_RELATED_TYPE)->delete();

        $unreadRows = ConversationParticipant::query()
            ->whereNull('left_at')
            ->whereRaw('COALESCE(last_read_message_id, 0) < (SELECT COALESCE(MAX(id), 0) FROM messages WHERE messages.conversation_id = conversation_participants.conversation_id)')
            ->get(['user_id', 'conversation_id']);

        if ($unreadRows->isEmpty()) {
            $this->info('No unread chats — nothing to remind.');

            return self::SUCCESS;
        }

        $activeUserIds = User::query()
            ->whereIn('id', $unreadRows->pluck('user_id')->unique())
            ->where('is_active', true)
            ->pluck('id');

        $reminded = 0;

        foreach ($unreadRows->groupBy('user_id') as $userId => $rows) {
            $userId = (int) $userId;

            if (! $activeUserIds->contains($userId)) {
                continue;
            }

            $this->remind($userId, $rows->pluck('conversation_id')->unique()->values());
            $reminded++;
        }

        $this->info("Reminded {$reminded} user(s) about unread chats.");

        return self::SUCCESS;
    }

    private function remind(int $userId, Collection $conversationIds): void
    {
        $count = $conversationIds->count();
        $chatWord = $count === 1 ? 'chat' : 'chats';

        UserNotification::query()->create([
            'user_id' => $userId,
            'type' => 'chat',
            'title' => 'Unread Messages',
            'message' => "You have unread messages in {$count} {$chatWord}. Catch up before it piles up!",
            'telegram_message' => "💬 <b>Unread Messages</b>\n\nYou have unread messages in {$count} {$chatWord}. Tap below to catch up.",
            'related_type' => self::REMINDER_RELATED_TYPE,
            // A single unread chat deep-links straight into it; several
            // fall back to the chat list (see UserNotification::
            // resolveUnreadChatUrl()).
            'related_id' => $count === 1 ? $conversationIds->first() : null,
            'is_read' => false,
        ]);
    }
}
