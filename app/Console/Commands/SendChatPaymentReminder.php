<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\SystemSetting;
use App\Models\TripPayment;
use App\Services\ChatService;
use Illuminate\Console\Command;

/**
 * Scheduled daily (see bootstrap/app.php). Unlike SendPendingPaymentApprovalReminder
 * and SendPaymentGraceDeadlineWarning (both notification-bell/Telegram, tied to the
 * monthly reliability-score deadline — often 40+ days after the trip), this one
 * posts straight into the trip chat itself, because the chat is only alive for
 * chat_retention_days_after days after the trip (well short of that deadline) and
 * the owner wanted a lighter nudge that lives there instead.
 *
 * De-dupes against `messages` (type=payment_reminder) rather than a notification
 * table — there's no notification involved, the reminder *is* the chat message.
 */
class SendChatPaymentReminder extends Command
{
    private const REPEAT_INTERVAL_DAYS = 3;

    protected $signature = 'chats:payment-reminder';

    protected $description = 'Post a Hexa reminder into trip chats where passengers still owe payment';

    public function handle(ChatService $chatService): int
    {
        $retentionDays = (int) (SystemSetting::get('chat_retention_days_after') ?? 30);

        $conversations = Conversation::query()
            ->whereNotNull('trip_id')
            ->whereNotNull('trip_datetime_snapshot')
            ->where('trip_datetime_snapshot', '<=', now()->subDay())
            ->get();

        $reminded = 0;

        foreach ($conversations as $conversation) {
            if ($this->recentlyReminded($conversation)) {
                continue;
            }

            $outstanding = TripPayment::query()
                ->where('trip_id', $conversation->trip_id)
                ->outstanding()
                ->with('user')
                ->get();

            if ($outstanding->isEmpty()) {
                continue;
            }

            $purgeAt = $conversation->scheduled_purge_at
                ?? $conversation->trip_datetime_snapshot->clone()->addDays($retentionDays);

            if (now()->greaterThan($purgeAt)) {
                continue;
            }

            $daysUntilClose = (int) now()->diffInDays($purgeAt, absolute: true);

            $chatService->postPaymentReminder($conversation, $outstanding, $daysUntilClose);
            $reminded++;
        }

        $this->info("Posted payment reminders in {$reminded} conversation(s).");

        return self::SUCCESS;
    }

    private function recentlyReminded(Conversation $conversation): bool
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('type', Message::TYPE_PAYMENT_REMINDER)
            ->where('created_at', '>=', now()->subDays(self::REPEAT_INTERVAL_DAYS))
            ->exists();
    }
}
