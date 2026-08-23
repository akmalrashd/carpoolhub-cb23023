<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

/**
 * One-off setup: registers this app's /telegram/webhook URL with Telegram so
 * it starts POSTing updates there. Run once per environment after
 * TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET are set in .env and the app
 * is reachable over a real public HTTPS URL — Telegram will not call
 * localhost/127.0.0.1, so this has no effect on a bare local dev server
 * unless it's tunnelled (ngrok, Cloudflare Tunnel, etc.).
 */
class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Register this app\'s webhook URL with the Telegram Bot API';

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');
        $secret = config('services.telegram.webhook_secret');

        if (empty($token) || empty($secret)) {
            $this->error('TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET must both be set in .env first.');

            return self::FAILURE;
        }

        $url = route('telegram.webhook');

        if (! str_starts_with($url, 'https://')) {
            $this->error("Webhook URL resolved to {$url} — Telegram requires https://. Check APP_URL in .env.");

            return self::FAILURE;
        }

        $this->info("Registering webhook: {$url}");

        $client = new Client(['base_uri' => "https://api.telegram.org/bot{$token}/"]);
        $response = $client->post('setWebhook', [
            'json' => ['url' => $url, 'secret_token' => $secret],
        ]);

        $body = json_decode((string) $response->getBody(), true);

        if ($body['ok'] ?? false) {
            $this->info('Webhook registered successfully.');

            return self::SUCCESS;
        }

        $this->error('Telegram rejected the request: ' . ($body['description'] ?? 'unknown error'));

        return self::FAILURE;
    }
}
