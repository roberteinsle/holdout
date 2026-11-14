<?php

namespace App\Support;

/**
 * Holdout colour palette (RAL-based: olive, sand, signal orange).
 *
 * Filament 5 generates shades from only the hue of a hex colour, with a fixed,
 * high chroma, which turns the muted olive into a bright yellow-green. So the
 * palettes are spelled out by hand. Filament uses shade 600 for filled buttons
 * and picks white or dark text on it by contrast; 400 is the dark-mode text shade.
 */
final class Palette
{
    /** @return array<string, array<int, string>> */
    public static function filament(): array
    {
        return [
            // olive-900 #4A5342 at 600, olive-300 #A7AD6D at 400 (dark-mode primary)
            'primary' => [
                50 => '#F4F5EF', 100 => '#E7E9DC', 200 => '#D2D6BC', 300 => '#BCC195',
                400 => '#A7AD6D', 500 => '#616847', 600 => '#4A5342', 700 => '#475135',
                800 => '#3A4230', 900 => '#2E3427', 950 => '#151619',
            ],
            // olive-600 #616847: "OK / enough stock"
            'success' => [
                50 => '#F4F5EF', 100 => '#E7E9DC', 200 => '#D2D6BC', 300 => '#BCC195',
                400 => '#A7AD6D', 500 => '#7A8158', 600 => '#616847', 700 => '#4A5342',
                800 => '#3A4230', 900 => '#2E3427', 950 => '#151619',
            ],
            // signal-500 #FF860F: "eat soon / low stock". Never white text on it.
            'warning' => [
                50 => '#FFF5EB', 100 => '#FFE8D1', 200 => '#FFCF9F', 300 => '#FFB46B',
                400 => '#FF9C3D', 500 => '#FF860F', 600 => '#FF860F', 700 => '#A35200',
                800 => '#8A4500', 900 => '#6B3600', 950 => '#242529',
            ],
            // alert-600 #D91E00 (light), alert-500 #FF2300 (dark): "missing / expired"
            'danger' => [
                50 => '#FFF1EE', 100 => '#FFDFD8', 200 => '#FFBBAD', 300 => '#FF8A73',
                400 => '#FF5A3A', 500 => '#FF2300', 600 => '#D91E00', 700 => '#B01800',
                800 => '#8A1300', 900 => '#6B0F00', 950 => '#3D0800',
            ],
            // Warm RAL greys; 500 = quartz grey #615E59 (Filament's muted text, 6.5:1 on white),
            // 400 = stone grey #928F88 (muted text in dark mode), 900/950 = tar/jet black.
            'gray' => [
                50 => '#F8F6F2', 100 => '#EFEBE4', 200 => '#DDD6CA', 300 => '#BCAE8B',
                400 => '#928F88', 500 => '#615E59', 600 => '#55524D', 700 => '#45433F',
                800 => '#2F3034', 900 => '#242529', 950 => '#151619',
            ],
            // brown-700 #57493C
            'info' => [
                50 => '#F7F4F1', 100 => '#ECE5DE', 200 => '#D8CBBE', 300 => '#BBA894',
                400 => '#9C8670', 500 => '#7A6652', 600 => '#57493C', 700 => '#4A3E33',
                800 => '#3C3229', 900 => '#2E2720', 950 => '#1C1813',
            ],
        ];
    }
}
