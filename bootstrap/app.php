<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PreventCaching;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // This is the app's first scheduled task — it does nothing on its
        // own. Laravel's scheduler only runs when something calls
        // `php artisan schedule:run`, which needs a real cPanel cron entry
        // on Hostinger (there is none yet): a single line running every
        // minute, e.g. `* * * * * php /home/.../artisan schedule:run
        // >> /dev/null 2>&1`. Without that cron entry this command below
        // will never fire no matter how correct this definition is.
        //
        // 3rd of the month, not the 1st — a few days' buffer so payments
        // confirmed right at the month boundary have settled, instead of
        // reporting a balance someone already cleared.
        $schedule->command('notifications:monthly-payment-summary')
            ->monthlyOn(3, '09:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureUserHasRole::class,
            'no-cache' => PreventCaching::class,
        ]);

        // Telegram's servers call this directly — no browser session, no
        // CSRF token to send. The X-Telegram-Bot-Api-Secret-Token header
        // (verified inside TelegramController::webhook) is the real guard.
        $middleware->validateCsrfTokens(except: ['telegram/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
