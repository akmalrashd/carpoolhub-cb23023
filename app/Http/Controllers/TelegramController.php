<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    private const LINK_CACHE_PREFIX = 'telegram_link:';

    public function __construct(private readonly TelegramService $telegram) {}

    /**
     * Mints a one-time token inside the already-authenticated session and
     * hands it to Telegram as a deep link — never the other way around.
     * Anyone can message a bot claiming to be anyone, so the bot is never
     * trusted to identify the user; only this token, born from a real
     * logged-in request, can.
     */
    public function link(Request $request): RedirectResponse
    {
        $botUsername = config('services.telegram.bot_username');

        if (empty(config('services.telegram.bot_token')) || empty($botUsername)) {
            return back()->with('status', 'Telegram belum dikonfigurasi. Sila hubungi admin.');
        }

        $token = Str::random(32);
        Cache::put(self::LINK_CACHE_PREFIX . $token, $request->user()->id, now()->addMinutes(10));

        return redirect()->away("https://t.me/{$botUsername}?start={$token}");
    }

    public function unlink(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
        ])->save();

        return back()->with('status', 'Telegram disconnected.');
    }

    /**
     * Called by Telegram itself, not the browser — no session, no CSRF
     * token, so it's excluded from CSRF verification in bootstrap/app.php.
     * The secret-token header is what stands in for that: the webhook URL
     * is public by definition, so without it anyone who finds the URL
     * could post fake updates.
     */
    public function webhook(Request $request): JsonResponse
    {
        $expected = config('services.telegram.webhook_secret');
        $given = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (empty($expected) || ! hash_equals($expected, (string) $given)) {
            abort(403);
        }

        $text = (string) $request->input('message.text', '');
        $chatId = $request->input('message.chat.id');
        $username = $request->input('message.from.username');

        if ($chatId && str_starts_with($text, '/start ')) {
            $this->handleStart(substr($text, 7), (string) $chatId, $username ? (string) $username : null);
        } elseif ($chatId && trim($text) === '/start') {
            $this->telegram->sendRaw((string) $chatId, "Untuk sambungkan akaun CarpoolHub anda, klik butang \"Connect Telegram\" dalam <b>Settings &gt; Notifications</b> pada app — bukan taip di sini.");
        }

        // Telegram expects a fast 200 regardless of outcome, or it will
        // keep retrying this same update.
        return response()->json(['ok' => true]);
    }

    private function handleStart(string $token, string $chatId, ?string $username): void
    {
        $cacheKey = self::LINK_CACHE_PREFIX . $token;
        $userId = Cache::get($cacheKey);

        if (! $userId) {
            $this->telegram->sendRaw($chatId, 'Link ni dah tamat tempoh atau tak sah. Sila cuba sambung semula dari <b>Settings &gt; Notifications</b> dalam app CarpoolHub.');
            return;
        }

        $user = User::query()->find($userId);
        if (! $user) {
            Cache::forget($cacheKey);
            return;
        }

        // A chat id already linked to a different account gets reassigned
        // rather than left dangling on the old one — one Telegram chat can
        // only ever notify one CarpoolHub user at a time.
        User::query()->where('telegram_chat_id', $chatId)->update(['telegram_chat_id' => null, 'telegram_username' => null]);

        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
        ])->save();

        Cache::forget($cacheKey);

        $this->telegram->sendRaw($chatId, '✅ Akaun CarpoolHub anda (<b>' . e($user->name) . '</b>) dah disambungkan! Notifikasi trip, payment, dan connection akan terus sampai sini.');
    }
}
