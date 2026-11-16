<?php

namespace App\Modules\Inventory\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a batch stands relative to its best-before date and the household's
 * "eat soon" window (households.eat_soon_days).
 */
enum ExpiryStatus: string implements HasColor, HasLabel
{
    case Expired = 'expired';
    case EatSoon = 'eat_soon';
    case Fresh = 'fresh';
    case NoDate = 'no_date';

    public static function for(?CarbonInterface $bestBefore, int $eatSoonDays, ?CarbonInterface $today = null): self
    {
        $today = ($today ?? now())->copy()->startOfDay();

        return match (true) {
            $bestBefore === null => self::NoDate,
            $bestBefore->lt($today) => self::Expired,
            $bestBefore->lte($today->copy()->addDays($eatSoonDays)) => self::EatSoon,
            default => self::Fresh,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Expired => __('Expired'),
            self::EatSoon => __('Eat soon'),
            self::Fresh => __('Fresh'),
            self::NoDate => __('No date'),
        };
    }

    /** Palette: danger = expired, warning (signal orange, dark text) = eat soon. */
    public function getColor(): string
    {
        return match ($this) {
            self::Expired => 'danger',
            self::EatSoon => 'warning',
            self::Fresh => 'success',
            self::NoDate => 'gray',
        };
    }
}
