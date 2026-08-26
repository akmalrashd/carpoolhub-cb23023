<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Your account is inactive.');
        }

        if ($user->is_active) {
            return $next($request);
        }

        // A pending/rejected driver has something to actually do about their
        // own inactivity — resubmit documents in Settings — unlike a plain
        // suspension, which stays a hard "contact support" wall. Scoped to
        // exactly the routes that flow needs; every other page still 403s.
        if ($user->isDriverAwaitingSelfService() && $this->isSelfServiceRoute($request)) {
            return $next($request);
        }

        abort(403, 'Your account is inactive.');
    }

    private function isSelfServiceRoute(Request $request): bool
    {
        $name = (string) ($request->route()?->getName() ?? '');

        return str_starts_with($name, 'settings.')
            || $name === 'profile.index'
            || str_starts_with($name, 'notifications.')
            || str_starts_with($name, 'refresh.notifications.')
            || str_starts_with($name, 'push.')
            || str_starts_with($name, 'telegram.')
            || $name === 'logout';
    }
}

