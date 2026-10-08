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

        // A driver who is pending or rejected can fix their own situation by
        // resubmitting documents in Settings. A plain suspension is different,
        // because there is nothing they can do except contact support. This is
        // limited to the routes that flow needs, and every other page still
        // returns 403.
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

