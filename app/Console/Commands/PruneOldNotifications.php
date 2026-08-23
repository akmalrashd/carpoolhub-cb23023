<?php

namespace App\Console\Commands;

use App\Models\UserNotification;
use Illuminate\Console\Command;

/**
 * Scheduled weekly (see bootstrap/app.php). Read and unread notifications
 * get different retention: a read notification has already been seen and
 * acted on (or dismissed), so it's safe to clear after a few months. An
 * unread one isn't necessarily still relevant either — plenty of users just
 * never bother tapping things read — so it only gets one extra month of
 * grace, not an open-ended pass. Before that grace period runs out, each
 * affected user gets one warning notification so nothing valuable (e.g. an
 * outstanding-payment reminder) disappears silently.
 *
 * This only trims the in-app notifications table; it never touches Telegram
 * chat history, which is a separate record the user still has either way.
 */
class PruneOldNotifications extends Command
{
    private const READ_RETENTION_MONTHS = 3;

    private const UNREAD_RETENTION_MONTHS = 4;

    /** How long before deletion the "still unread" warning fires. */
    private const UNREAD_WARNING_MONTHS = 3;

    /**
     * Marks a warning notification so it's never counted as a trigger for
     * another warning, and so re-runs within the same warning window (the
     * gap between UNREAD_WARNING_MONTHS and UNREAD_RETENTION_MONTHS is a
     * month, but this command runs weekly) don't re-send it four times over.
     */
    private const WARNING_RELATED_TYPE = 'unread_notifications_warning';

    private const WARNING_COOLDOWN_DAYS = 25;

    protected $signature = 'notifications:prune';

    protected $description = 'Warn users about aging unread notifications, then delete old in-app notifications — read ones after 3 months, unread ones after 4';

    public function handle(): int
    {
        $warned = $this->warnAboutAgingUnread();

        $readDeleted = UserNotification::query()
            ->where('is_read', true)
            ->where('created_at', '<', now()->subMonths(self::READ_RETENTION_MONTHS))
            ->delete();

        $unreadDeleted = UserNotification::query()
            ->where('is_read', false)
            ->where('created_at', '<', now()->subMonths(self::UNREAD_RETENTION_MONTHS))
            ->delete();

        $this->info("Warned {$warned} user(s) about aging unread notifications. Pruned {$readDeleted} read and {$unreadDeleted} unread notification(s).");

        return self::SUCCESS;
    }

    private function warnAboutAgingUnread(): int
    {
        $warnAt = now()->subMonths(self::UNREAD_WARNING_MONTHS);
        $deleteAt = now()->subMonths(self::UNREAD_RETENTION_MONTHS);
        $monthsLeft = self::UNREAD_RETENTION_MONTHS - self::UNREAD_WARNING_MONTHS;

        $userIds = UserNotification::query()
            ->where('is_read', false)
            ->where('created_at', '<', $warnAt)
            ->where('created_at', '>=', $deleteAt)
            ->where(function ($query): void {
                $query->whereNull('related_type')
                    ->orWhere('related_type', '!=', self::WARNING_RELATED_TYPE);
            })
            ->distinct()
            ->pluck('user_id');

        $warned = 0;

        foreach ($userIds as $userId) {
            $alreadyWarned = UserNotification::query()
                ->where('user_id', $userId)
                ->where('related_type', self::WARNING_RELATED_TYPE)
                ->where('created_at', '>=', now()->subDays(self::WARNING_COOLDOWN_DAYS))
                ->exists();

            if ($alreadyWarned) {
                continue;
            }

            UserNotification::query()->create([
                'user_id' => $userId,
                'type' => 'system',
                'title' => 'Unread Notifications Will Be Deleted Soon',
                'message' => "You have notifications you still haven't read. They'll be deleted automatically if they stay unread for another {$monthsLeft} month(s) — open them to keep them.",
                'related_type' => self::WARNING_RELATED_TYPE,
                'related_id' => null,
                'is_read' => false,
            ]);
            $warned++;
        }

        return $warned;
    }
}
