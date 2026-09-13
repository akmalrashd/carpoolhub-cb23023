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

        // Daily, inside the chat itself rather than a notification — the chat is
        // only alive for chat_retention_days_after days after the trip, well before
        // the monthly reliability-score deadline the two reminders above track, so
        // this is a separate, lighter-weight nudge that stops once the chat closes.
        $schedule->command('chats:payment-reminder')
            ->dailyAt('11:00');

        // Every 5 minutes, not daily like the reminders above — a temporary
        // suspension can expire at any minute and the account should regain
        // access promptly, not sit needlessly suspended for up to a day.
        $schedule->command('users:reactivate-expired-suspensions')
            ->everyFiveMinutes();

        // Third safety net behind the ToyyibPay Return URL and Callback —
        // catches a passenger who paid but closed the tab before the Return
        // URL redirect fired. 15 minutes gives those two a fair chance first.
        $schedule->command('payments:reconcile-gateway-transactions')
            ->everyFifteenMinutes();

        // Off-peak, once a day is plenty — a chat's grace period is measured
        // in days, so there's no urgency to purge it the same hour it expires.
        $schedule->command('chats:purge-expired')
            ->dailyAt('03:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureUserHasRole::class,
            'no-cache' => PreventCaching::class,
        ]);

        // Telegram's and ToyyibPay's servers call these directly — no
        // browser session, no CSRF token to send. The
        // X-Telegram-Bot-Api-Secret-Token header and the ToyyibPay MD5 hash
        // (verified inside their respective controllers) are the real guards.
        $middleware->validateCsrfTokens(except: ['telegram/webhook', 'payments/gateway/callback']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
