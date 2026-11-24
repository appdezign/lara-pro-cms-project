<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * a request on another host than APP_URL's (example.com instead of www.example.com)
 * gets a 301 to APP_URL with the same path and query string.
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production')) {
            return $next($request);
        }

        $canonical = parse_url((string) config('app.url'));
        $canonicalHost = strtolower($canonical['host'] ?? '');

        if ($canonicalHost === '' || strtolower($request->getHost()) === $canonicalHost) {
            return $next($request);
        }

        $target = ($canonical['scheme'] ?? 'https').'://'.$canonicalHost
            .(isset($canonical['port']) ? ':'.$canonical['port'] : '')
            .$request->getRequestUri();

        return redirect()->away($target, 301);
    }
}
