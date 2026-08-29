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
        // Requires the cPanel cron entry on Hostinger calling
        // `php artisan schedule:run` every minute — already set up.
        //
        // 25th — about 9 days before the payment summary below, so a driver
        // who forgot to log a trip mid-month has real time to add it before
        // that summary treats whatever's in the system as final.
        $schedule->command('notifications:trip-entry-reminder')
            ->monthlyOn(25, '19:00');

        // 3rd of the month, not the 1st — a few days' buffer so payments
        // confirmed right at the month boundary have settled, instead of
        // reporting a balance someone already cleared.
        $schedule->command('notifications:monthly-payment-summary')
            ->monthlyOn(3, '09:00');

        // Weekly is enough for a cleanup task — no need for a daily run.
        // Monday, off-hours, so it never lands on the same run as the
        // reminders above.
        $schedule->command('notifications:prune')
            ->weeklyOn(1, '04:00');

        // Daily, not weekly like the prune job above — the grace window
        // before a driver first gets nagged is only a few days, so a weekly
        // check would badly lag behind it for anyone who crosses the
        // threshold early in the week.
        $schedule->command('notifications:pending-payment-reminder')
            ->dailyAt('10:00');

        // Also daily (the deadline it watches for shifts with month length,
        // so a fixed day-of-month wouldn't line up) — runs before the driver
        // reminder above so a passenger who acts on it same-day never crosses
        // paths with a same-day driver nag for the same trip.
        $schedule->command('notifications:payment-grace-reminder')
            ->dailyAt('09:00');

        // Every 5 minutes, not daily like the reminders above — a temporary
        // suspension can expire at any minute and the account should regain
        // access promptly, not sit needlessly suspended for up to a day.
        $schedule->command('users:reactivate-expired-suspensions')
            ->everyFiveMinutes();
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
