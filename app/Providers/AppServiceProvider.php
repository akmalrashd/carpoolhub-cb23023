<?php

namespace App\Providers;

use App\Models\UserNotification;
use App\Observers\UserNotificationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        UserNotification::observe(UserNotificationObserver::class);

        // Shared bucket for every route that bills a real Anthropic call
        // (chat, fare-advice, recommend-route). Previously each route had
        // its own throttle:30,1, so a user could actually reach ~90
        // AI-billed requests/min by hitting all three at once. One named
        // limiter here closes that gap and adds a daily spend ceiling.
        //
        // The last Limit uses a fixed key (not per-user) — a platform-wide
        // circuit breaker so total spend can't scale unbounded with the
        // number of accounts (real or fake) even though each one individually
        // stays under its own per-user cap. Once it trips, every user gets
        // the same graceful "AI unavailable" fallback the frontend already
        // shows for any failed /ai/* call — see resources/views/components/
        // ai-chat.blade.php's sendMessage() catch-all.
        RateLimiter::for('ai-spend', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(30)->by("ai-spend-min:{$key}"),
                Limit::perDay((int) config('ai_chat.daily_limit', 150))->by("ai-spend-day:{$key}"),
                Limit::perDay((int) config('ai_chat.global_daily_limit', 3000))->by('ai-spend-day:global'),
            ];
        });
    }
}
