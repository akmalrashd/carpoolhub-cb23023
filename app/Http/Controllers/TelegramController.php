<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Auto-login for a Telegram Mini App launch (e.g. the "Open in App"
     * button on a notification, opened as a web_app instead of an external
     * browser). Telegram's own webview has no CarpoolHub session cookie —
     * without this, every Mini App open would dead-end on the login form.
     *
     * initData is signed by Telegram using a key derived from the bot
     * token, so a verified initData.user.id is exactly as trustworthy as
     * the /start deep-link flow that originally linked it — the difference
     * is this path only ever recognises an ALREADY-linked account. Someone
     * who has never connected Telegram still links the normal way, from an
     * authenticated browser session (Settings > Notifications), because
     * that's the one place a fresh chat id can be tied to a user at all.
     */
    public function miniAppAuth(Request $request): JsonResponse
    {
        $data = $this->validateInitData((string) $request->input('initData', ''));

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired Telegram session.'], 422);
        }

        $telegramUser = json_decode((string) ($data['user'] ?? '{}'), true);
        $telegramUserId = (string) ($telegramUser['id'] ?? '');

        if ($telegramUserId === '') {
            return response()->json(['success' => false, 'message' => 'Invalid Telegram user data.'], 422);
        }

        $user = User::query()->where('telegram_chat_id', $telegramUserId)->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'not_linked'], 404);
        }

        if (! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'account_inactive'], 403);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect' => $request->session()->pull('url.intended', route('home')),
        ]);
    }

    /**
     * Verifies initData per Telegram's documented algorithm: secret_key =
     * HMAC-SHA256(bot_token, key="WebAppData"), then the check-string (every
     * field but hash, sorted, joined "key=value" with \n) is HMAC-SHA256'd
     * with that secret and compared to the received hash. Returns the
     * parsed fields on success, null on any failure — including stale data
     * (auth_date older than 24h), which blocks replaying a captured
     * initData indefinitely.
     */
    private function validateInitData(string $initData): ?array
    {
        if ($initData === '' || empty(config('services.telegram.bot_token'))) {
            return null;
        }

        parse_str($initData, $data);

        if (empty($data['hash']) || ! is_array($data)) {
            return null;
        }

        $hash = (string) $data['hash'];
        unset($data['hash']);

        ksort($data);
        $checkString = collect($data)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac('sha256', config('services.telegram.bot_token'), 'WebAppData', true);
        $computedHash = hash_hmac('sha256', $checkString, $secretKey);

        if (! hash_equals($computedHash, $hash)) {
            return null;
        }

        if (empty($data['auth_date']) || (time() - (int) $data['auth_date']) > 86400) {
            return null;
        }

        return $data;
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
