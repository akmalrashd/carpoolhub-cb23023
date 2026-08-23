<?php

namespace App\Observers;

use App\Models\UserNotification;
use App\Services\PushService;
use App\Services\TelegramService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Push delivery is deferred until the surrounding transaction commits.
 *
 * Notifications are created inside DB::transaction() throughout PaymentService
 * and TripJoinRequestService, and sendToUser() makes blocking HTTPS calls to the
 * browser push services. Firing it from inside the transaction held row locks
 * open for the duration of those calls — and, in the bulk payment actions, did
 * so once per payment in a single request. It also meant a push could be
 * delivered for a notification whose transaction then rolled back.
 *
 * ShouldHandleEventsAfterCommit moves delivery to just after commit. It stays in
 * the same request rather than being queued on purpose: there is no queue worker
 * in the deploy (see deploy.sh), so a queued job would never run and push
 * notifications would silently stop.
 */
class UserNotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly PushService $pushService,
        private readonly TelegramService $telegramService,
    ) {}

    public function created(UserNotification $notification): void
    {
        $user = $notification->user;
        if (! $user) {
            return;
        }

        try {
            $this->pushService->sendToUser($user, $notification);
        } catch (\Throwable) {
            // Never break the main request if push fails
        }

        try {
            $this->telegramService->sendToUser($user, $notification);
        } catch (\Throwable) {
            // Never break the main request if Telegram delivery fails
        }
    }
}
