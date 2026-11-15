<?php

namespace App\Modules\Inventory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Unit of a package size. For the supply calculation, amounts are converted to
 * the category units kg and l, with 1 l counted as 1 kg (milk is sold in litres
 * but belongs to a kg category).
 */
enum PackageUnit: string implements HasLabel
{
    case Gram = 'g';
    case Kilogram = 'kg';
    case Millilitre = 'ml';
    case Litre = 'l';

    public function getLabel(): string
    {
        return $this->value;
    }

    /** Factor to kg or l (both counted the same). */
    public function toBaseFactor(): float
    {
        return match ($this) {
            self::Gram, self::Millilitre => 0.001,
            self::Kilogram, self::Litre => 1.0,
        };
    }
}
