<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PreventCaching;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
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
