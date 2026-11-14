<?php

namespace App\Filament\Support;

use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\ButtonComponent as FilamentButtonComponent;

/**
 * Filled buttons in the colours of the design doc. Filament's automatic choice
 * would put the olive on its light shade with dark text; we want olive-900 with
 * white text in light mode and olive-300 with dark text in dark mode, and
 * never white text on signal orange.
 */
class ButtonComponent extends FilamentButtonComponent
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        if ($this->isOutlined) {
            return parent::getColorMap($color);
        }

        $name = collect(['primary', 'success', 'warning', 'danger', 'info'])
            ->first(fn (string $name): bool => FilamentColor::getColor($name) === $color);

        return match ($name) {
            'primary', 'success' => $this->map(bg: 600, hover: 700, text: 0, darkBg: 400, darkHover: 300, darkText: 950),
            'warning' => $this->map(bg: 500, hover: 400, text: 950, darkBg: 500, darkHover: 400, darkText: 950),
            'danger', 'info' => $this->map(bg: 600, hover: 700, text: 0, darkBg: 600, darkHover: 700, darkText: 0),
            default => parent::getColorMap($color),
        };
    }

    /** Shade numbers; text 0 means white. */
    private function map(int $bg, int $hover, int $text, int $darkBg, int $darkHover, int $darkText): array
    {
        return [
            'bg' => $bg,
            'hover:bg' => $hover,
            'dark:bg' => $darkBg,
            'dark:hover:bg' => $darkHover,
            'text' => $text,
            'hover:text' => $text,
            'dark:text' => $darkText,
            'dark:hover:text' => $darkText,
        ];
    }
}
