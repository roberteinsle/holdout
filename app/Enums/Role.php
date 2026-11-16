<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    case Owner = 'owner';
    case Member = 'member';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => __('Owner'),
            self::Member => __('Member'),
        };
    }
}
