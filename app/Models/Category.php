<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A supply category (e.g. drinks, grains). Rows come from the CategoryRegistry
 * via CategorySeeder; do not edit them by hand.
 */
#[Fillable(['key', 'module', 'name', 'need_unit', 'need_per_adult_day', 'need_per_child_day', 'is_water', 'sort', 'source'])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'need_per_adult_day' => 'float',
            'need_per_child_day' => 'float',
            'is_water' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /** Translated display name. */
    public function label(): string
    {
        return __($this->name);
    }
}
