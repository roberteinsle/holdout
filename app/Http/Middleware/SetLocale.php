<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed-in users get their household's language; guests get the best match
 * from the browser's Accept-Language header.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'de'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->household?->locale
            ?? $request->getPreferredLanguage(self::SUPPORTED)
            ?? config('app.locale');

        if (in_array($locale, self::SUPPORTED, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
