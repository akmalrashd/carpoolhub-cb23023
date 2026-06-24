<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use App\Models\UserNotification;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushService
{
    private function webPush(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject'    => config('app.vapid_subject'),
                'publicKey'  => config('app.vapid_public_key'),
                'privateKey' => config('app.vapid_private_key'),
            ],
        ]);
    }

    public function saveSubscription(User $user, array $data, ?string $userAgent = null): PushSubscription
    {
        $endpoint  = $data['endpoint'];
        $p256dh    = $data['keys']['p256dh'] ?? '';
        $auth      = $data['keys']['auth'] ?? '';

        $existing = PushSubscription::query()
            ->where('user_id', $user->id)
            ->whereRaw('endpoint = ?', [$endpoint])
            ->first();

        if ($existing) {
            $existing->update([
                'public_key' => $p256dh,
                'auth_token' => $auth,
                'user_agent' => $userAgent,
            ]);
            return $existing;
        }

        return PushSubscription::query()->create([
            'user_id'    => $user->id,
            'endpoint'   => $endpoint,
            'public_key' => $p256dh,
            'auth_token' => $auth,
            'user_agent' => $userAgent,
        ]);
    }

    public function deleteSubscription(User $user, string $endpoint): void
    {
        PushSubscription::query()
            ->where('user_id', $user->id)
            ->whereRaw('endpoint = ?', [$endpoint])
            ->delete();
    }

    public function sendToUser(User $user, UserNotification $notification): void
    {
        $subscriptions = PushSubscription::query()
            ->where('user_id', $user->id)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title'   => $notification->title,
            'message' => $notification->message,
            'url'     => $notification->target_url,
            'type'    => $notification->type,
        ]);

        $webPush = $this->webPush();

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'keys'            => [
                        'p256dh' => $sub->public_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::query()
                    ->where('endpoint', $report->getEndpoint())
                    ->delete();
            }
        }
    }
}
