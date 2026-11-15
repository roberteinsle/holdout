<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Languages of the app. English is the source language and the fallback.
 */
final class Locales
{
    /** @var array<string, string> code => name in that language */
    public const SUPPORTED = [
        'en' => 'English',
        'de' => 'Deutsch',
        'es' => 'Español',
        'pt' => 'Português',
        'fr' => 'Français',
    ];

    public const FALLBACK = 'en';

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::SUPPORTED);
    }

    public static function fromBrowser(Request $request): string
    {
        return $request->getPreferredLanguage(self::codes()) ?? self::FALLBACK;
    }
}
