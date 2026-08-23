<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For live-polled JSON endpoints (the /refresh/* group) — the app already
 * fights a Hostinger CDN layer that sits in front of it (see sw.js and
 * public/.htaccess), and unlike a static asset, a poll response has no
 * ?v=<filemtime> to change the URL when the underlying data changes. If
 * an intermediary caches one of these by response body/URL alone, every
 * client polling it gets the same stale snapshot until that cache expires,
 * no matter how often the browser actually re-requests it.
 */
class PreventCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
