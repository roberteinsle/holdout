<?php

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials avatar rendered locally as an SVG data URI. Filament's default
 * loads it from ui-avatars.com, which would send every user's name to a
 * third party on each page view.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(fn (string $part): string => mb_substr((string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $part), 0, 1))
            ->filter()
            ->take(2)
            ->join('');

        $initials = htmlspecialchars(mb_strtoupper($initials), ENT_QUOTES | ENT_XML1);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#4A5342"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#FFFFFF" '
            .'font-family="system-ui, sans-serif" font-size="26" font-weight="600">'.$initials.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
