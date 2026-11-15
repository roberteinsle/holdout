<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MemberType: string implements HasLabel
{
    case Adult = 'adult';
    case Child = 'child';

    public function getLabel(): string
    {
        return match ($this) {
            self::Adult => __('Adults'),
            self::Child => __('Children'),
        };
    }
}
