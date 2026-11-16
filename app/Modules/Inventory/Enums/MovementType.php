<?php

namespace App\Modules\Inventory\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/** What happened to the packages of a batch. */
enum MovementType: string implements HasColor, HasIcon, HasLabel
{
    /** A batch was added to the supply. */
    case Added = 'added';
    /** Packages were taken out and eaten or drunk. */
    case Consumed = 'consumed';
    /** Packages were thrown away, e.g. spoiled or long expired. */
    case Discarded = 'discarded';
    /** The amount was changed by hand, or the batch was deleted. */
    case Corrected = 'corrected';

    /** The reasons offered when taking packages out. */
    public static function takeOutReasons(): array
    {
        return [self::Consumed, self::Discarded];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Added => __('Added'),
            self::Consumed => __('Consumed'),
            self::Discarded => __('Discarded'),
            self::Corrected => __('Correction'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Added => 'success',
            self::Consumed => 'info',
            self::Discarded => 'danger',
            self::Corrected => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Added => Heroicon::OutlinedPlusCircle,
            self::Consumed => Heroicon::OutlinedMinusCircle,
            self::Discarded => Heroicon::OutlinedTrash,
            self::Corrected => Heroicon::OutlinedPencilSquare,
        };
    }
}
