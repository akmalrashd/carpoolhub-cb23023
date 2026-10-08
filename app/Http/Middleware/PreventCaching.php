<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used by the JSON endpoints that get polled live, the /refresh/* group. The
 * app already has to work around a CDN layer sitting in front of it on the
 * host, which sw.js and public/.htaccess also deal with. Unlike a static
 * asset, a poll response has no ?v=<filemtime> on its URL to change when the
 * data behind it changes. If
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
