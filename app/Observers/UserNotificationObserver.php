<?php

namespace App\Observers;

use App\Models\UserNotification;
use App\Services\PushService;

class UserNotificationObserver
{
    public function __construct(private readonly PushService $pushService) {}

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
    }
}
