<?php

namespace App\Models;

use App\Enums\MemberType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How many people of one type (adults or children) live in a household.
 */
#[Fillable(['type', 'count'])]
class Member extends Model
{
    protected function casts(): array
    {
        return [
            'type' => MemberType::class,
            'count' => 'integer',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
