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
        RateLimiter::for('ai-spend', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(30)->by("ai-spend-min:{$key}"),
                Limit::perDay((int) config('ai_chat.daily_limit', 150))->by("ai-spend-day:{$key}"),
            ];
        });
    }
}
