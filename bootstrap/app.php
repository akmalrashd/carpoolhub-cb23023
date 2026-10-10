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
        // `php artisan schedule:run` every minute, which is already set up.
        //
        // The 25th, about nine days before the payment summary below, so a
        // driver
        // who forgot to log a trip mid-month has real time to add it before
        // that summary treats whatever's in the system as final.
        $schedule->command('notifications:trip-entry-reminder')
            ->monthlyOn(25, '19:00');

        // The 3rd of the month rather than the 1st, leaving a few days so that
        // payments
        // confirmed right at the month boundary have settled, instead of
        // reporting a balance someone already cleared.
        $schedule->command('notifications:monthly-payment-summary')
            ->monthlyOn(3, '09:00');

        // Weekly is enough for a cleanup task, so there is no need to run it
        // daily.
        // Monday, off-hours, so it never lands on the same run as the
        // reminders above.
        $schedule->command('notifications:prune')
            ->weeklyOn(1, '04:00');

        // Daily rather than weekly like the prune job above, because the grace
        // window
        // before a driver first gets nagged is only a few days, so a weekly
        // check would badly lag behind it for anyone who crosses the
        // threshold early in the week.
        $schedule->command('notifications:pending-payment-reminder')
            ->dailyAt('10:00');

        // Also daily (the deadline it watches for shifts with month length,
        // so a fixed day of the month would not line up). It runs before the
        // driver
        // reminder above so a passenger who acts on it same-day never crosses
        // paths with a same-day driver nag for the same trip.
        $schedule->command('notifications:payment-grace-reminder')
            ->dailyAt('09:00');

        // Daily, posted inside the chat itself rather than as a notification,
        // because the chat is
        // only alive for chat_retention_days_after days after the trip, well before
        // the monthly reliability-score deadline the two reminders above track, so
        // this is a separate, lighter-weight nudge that stops once the chat closes.
        $schedule->command('chats:payment-reminder')
            ->dailyAt('11:00');

        // Runs after the chat payment reminder above so both daily chat
        // touches land close together. Unlike every other reminder in this
        // schedule, this one has no cooldown. It deletes and recreates its
        // own notification each run, so running it more than once a day is
        // harmless (just refreshes the same single row), not a duplication risk.
        $schedule->command('notifications:unread-chat-reminder')
            ->dailyAt('11:30');

        // Runs right after the unread-chat reminder above, keeping every
        // "daily chat/notification touch" clustered together. Posts a
        // one-time Hexa "how was your ride?" chat message on newly-completed
        // public trips, and like the reminder above it deletes and
        // recreates its own reminder for anyone still inside driver_rating_
        // window_days. Self-clears the day someone rates and stops once a
        // trip ages out of the window; no manual cleanup needed either way.
        $schedule->command('ratings:invite-reminder')
            ->dailyAt('11:45');

        // Every 5 minutes rather than daily like the reminders above, because
        // a temporary
        // suspension can expire at any minute and the account should regain
        // access promptly, not sit needlessly suspended for up to a day.
        $schedule->command('users:reactivate-expired-suspensions')
            ->everyFiveMinutes();

        // The third safety net behind the ToyyibPay Return URL and Callback.
        // catches a passenger who paid but closed the tab before the Return
        // URL redirect fired. 15 minutes gives those two a fair chance first.
        $schedule->command('payments:reconcile-gateway-transactions')
            ->everyFifteenMinutes();

        // Off peak, and once a day is plenty, because a chat's grace period is
        // measured
        // in days, so there's no urgency to purge it the same hour it expires.
        $schedule->command('chats:purge-expired')
            ->dailyAt('03:00');

        $schedule->command('chats:prune-circle-messages')
            ->dailyAt('03:15');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureUserHasRole::class,
            'no-cache' => PreventCaching::class,
        ]);

        // Telegram's and ToyyibPay's servers call these directly, so there is
        // no
        // browser session, no CSRF token to send. The
        // X-Telegram-Bot-Api-Secret-Token header and the ToyyibPay MD5 hash
        // (verified inside their respective controllers) are the real guards.
        $middleware->validateCsrfTokens(except: ['telegram/webhook', 'payments/gateway/callback']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
