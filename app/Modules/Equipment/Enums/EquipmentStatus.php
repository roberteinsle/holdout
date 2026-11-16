<?php

namespace App\Modules\Equipment\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Status of an equipment item, most urgent first. Always shown with icon and label.
 */
enum EquipmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Missing = 'missing';
    case Expired = 'expired';
    case NeedsAttention = 'attention';
    case CheckDue = 'check_due';
    case ExpiresSoon = 'expires_soon';
    case Ok = 'ok';

    public function getLabel(): string
    {
        return match ($this) {
            self::Missing => __('Missing'),
            self::Expired => __('Expired'),
            self::NeedsAttention => __('Needs attention'),
            self::CheckDue => __('Check due'),
            self::ExpiresSoon => __('Expires soon'),
            self::Ok => __('OK'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Missing, self::Expired => 'danger',
            self::NeedsAttention, self::CheckDue, self::ExpiresSoon => 'warning',
            self::Ok => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Missing => Heroicon::OutlinedXCircle,
            self::Expired => Heroicon::OutlinedExclamationCircle,
            self::NeedsAttention => Heroicon::OutlinedWrench,
            self::CheckDue => Heroicon::OutlinedClock,
            self::ExpiresSoon => Heroicon::OutlinedCalendar,
            self::Ok => Heroicon::OutlinedCheckCircle,
        };
    }
}
