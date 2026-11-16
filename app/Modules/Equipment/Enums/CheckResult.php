<?php

namespace App\Modules\Equipment\Enums;

use Filament\Support\Contracts\HasLabel;

enum CheckResult: string implements HasLabel
{
    case Ok = 'ok';
    case NeedsAttention = 'attention';
    case Replaced = 'replaced';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ok => __('OK, present and working'),
            self::NeedsAttention => __('Needs attention'),
            self::Replaced => __('Replaced or refilled'),
        };
    }
}
