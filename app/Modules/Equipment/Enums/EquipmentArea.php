<?php

namespace App\Modules\Equipment\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Checklist areas, following the BBK guidance on emergency preparedness,
 * plus "other equipment" for everything else (dishes, tools, …).
 */
enum EquipmentArea: string implements HasIcon, HasLabel
{
    case Pharmacy = 'pharmacy';
    case Power = 'power';
    case Hygiene = 'hygiene';
    case Documents = 'documents';
    case Cash = 'cash';
    case GrabBag = 'grab_bag';
    case Fire = 'fire';
    case Information = 'information';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pharmacy => __('Home pharmacy'),
            self::Power => __('Power outage'),
            self::Hygiene => __('Hygiene'),
            self::Documents => __('Documents'),
            self::Cash => __('Cash'),
            self::GrabBag => __('Grab bag'),
            self::Fire => __('Fire safety'),
            self::Information => __('Staying informed'),
            self::Other => __('Other equipment'),
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pharmacy => Heroicon::OutlinedHeart,
            self::Power => Heroicon::OutlinedBolt,
            self::Hygiene => Heroicon::OutlinedSparkles,
            self::Documents => Heroicon::OutlinedDocumentText,
            self::Cash => Heroicon::OutlinedBanknotes,
            self::GrabBag => Heroicon::OutlinedBriefcase,
            self::Fire => Heroicon::OutlinedFire,
            self::Information => Heroicon::OutlinedRadio,
            self::Other => Heroicon::OutlinedWrenchScrewdriver,
        };
    }

    public function sort(): int
    {
        return array_search($this, self::cases(), true);
    }
}
