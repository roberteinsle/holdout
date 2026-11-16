<?php

namespace App\Support;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/** How far a category's usable stock covers its target. Shown with colour, icon and label. */
enum StockLevel: string implements HasColor, HasIcon, HasLabel
{
    case Enough = 'enough';
    case Low = 'low';
    case Missing = 'missing';

    public static function for(float $stock, float $target): self
    {
        return match (true) {
            $stock >= $target => self::Enough,
            $stock > 0 => self::Low,
            default => self::Missing,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Enough => __('Enough'),
            self::Low => __('Too little'),
            self::Missing => __('Missing'),
        };
    }

    /** Palette roles: olive = enough, signal orange = low (dark text), red = missing. */
    public function getColor(): string
    {
        return match ($this) {
            self::Enough => 'success',
            self::Low => 'warning',
            self::Missing => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Enough => Heroicon::CheckCircle,
            self::Low => Heroicon::ExclamationTriangle,
            self::Missing => Heroicon::XCircle,
        };
    }
}
