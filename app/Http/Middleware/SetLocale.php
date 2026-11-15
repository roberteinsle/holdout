<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The person using this device gets the language from their profile; guests and
 * people who have not chosen one get the best match from the browser, else English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (! in_array($locale, Locales::codes(), true)) {
            $locale = Locales::fromBrowser($request);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
