<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private const TYPE_EMOJI = [
        'trip' => '🚗',
        'payment' => '💰',
        'connection' => '🤝',
        'route' => '📍',
        'system' => '🔔',
    ];

    private function client(): Client
    {
        return new Client([
            'base_uri' => 'https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/',
            'timeout' => 10,
        ]);
    }

    /**
     * Mirrors PushService::sendToUser() — same silent-skip-if-not-linked
     * shape, same "never break the caller" contract (the observer that
     * calls this already wraps it in try/catch, but a dead Telegram link
     * shouldn't keep failing forever either, so a blocked/kicked bot
     * self-heals by clearing the stored chat id below).
     */
    public function sendToUser(User $user, UserNotification $notification): void
    {
        if (empty($user->telegram_chat_id) || empty(config('services.telegram.bot_token'))) {
            return;
        }

        // telegram_message, when set, IS the complete text (own header,
        // formatting, line breaks) — for notifications where the in-app copy
        // has to stay short (views collapse newlines / hard-truncate to 2
        // lines) but Telegram can render something richer. Falls back to the
        // generic emoji+title+message shape used by every other notification.
        if (! empty($notification->telegram_message)) {
            $text = $notification->telegram_message;
        } else {
            $emoji = self::TYPE_EMOJI[$notification->type] ?? '🔔';
            $text = sprintf(
                "%s <b>%s</b>\n\n%s",
                $emoji,
                e($notification->title),
                e($notification->message)
            );
        }

        try {
            $response = $this->client()->post('sendMessage', [
                'json' => [
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            // web_app (not a plain url button) opens this inside
                            // Telegram's own webview on every platform — the
                            // login page there auto-signs the user in via
                            // TelegramController::miniAppAuth() using initData,
                            // instead of leaving them on a login form in a
                            // webview that never had a CarpoolHub session.
                            ['text' => 'Open in App', 'web_app' => ['url' => $notification->target_url]],
                        ]],
                    ],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (! ($body['ok'] ?? false) && $this->isDeadLink((string) ($body['description'] ?? ''))) {
                $this->unlink($user);
            }
        } catch (GuzzleException $e) {
            $status = $e->getCode();
            if ($status === 403) {
                $this->unlink($user);
            }
            Log::warning('Telegram send failed: ' . $e->getMessage(), ['user_id' => $user->id]);
        }
    }

    /**
     * Sends a plain message to a raw chat id — used by the webhook handler
     * for the "linked!" confirmation, before any User row is necessarily
     * resolved. sendToUser() above is for real notifications tied to a
     * UserNotification; this is the bare primitive it's built on.
     */
    public function sendRaw(string $chatId, string $text, ?array $replyMarkup = null): void
    {
        if (empty(config('services.telegram.bot_token'))) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        try {
            $this->client()->post('sendMessage', ['json' => $payload]);
        } catch (GuzzleException $e) {
            Log::warning('Telegram raw send failed: ' . $e->getMessage());
        }
    }

    private function isDeadLink(string $description): bool
    {
        $description = strtolower($description);

        return str_contains($description, 'blocked') || str_contains($description, 'chat not found') || str_contains($description, 'deactivated');
    }

    private function unlink(User $user): void
    {
        $user->forceFill(['telegram_chat_id' => null, 'telegram_username' => null])->save();
    }
}
