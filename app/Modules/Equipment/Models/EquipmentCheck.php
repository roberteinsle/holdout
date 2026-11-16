<?php

namespace App\Modules\Equipment\Models;

use App\Models\User;
use App\Modules\Equipment\Enums\CheckResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One entry in an item's check log: who checked it when, and with what result. */
#[Fillable(['user_id', 'result', 'note', 'checked_at'])]
class EquipmentCheck extends Model
{
    protected function casts(): array
    {
        return [
            'result' => CheckResult::class,
            'checked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EquipmentItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(EquipmentItem::class, 'equipment_item_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
