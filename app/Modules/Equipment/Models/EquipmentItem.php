<?php

namespace App\Modules\Equipment\Models;

use App\Models\Household;
use App\Models\User;
use App\Modules\Equipment\Enums\CheckResult;
use App\Modules\Equipment\Enums\EquipmentArea;
use App\Modules\Equipment\Enums\EquipmentStatus;
use Database\Factories\EquipmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An equipment or checklist item (e.g. a camping stove, the first aid kit, cash).
 * available = the household has it; attention = the last check found a problem.
 */
#[Fillable(['area', 'name', 'quantity', 'available', 'expires_on', 'check_interval_months', 'location', 'note'])]
class EquipmentItem extends Model
{
    /** @use HasFactory<EquipmentItemFactory> */
    use HasFactory;

    /** Expiry dates within this many days are shown as "expires soon". */
    public const EXPIRES_SOON_DAYS = 60;

    protected static function newFactory(): EquipmentItemFactory
    {
        return EquipmentItemFactory::new();
    }

    protected static function booted(): void
    {
        // The next check follows from the last one (or from when the item was added).
        static::saving(function (EquipmentItem $item): void {
            $base = $item->last_checked_at ?? $item->created_at ?? now();

            $item->next_check_on = $item->check_interval_months
                ? Carbon::parse($base)->addMonthsNoOverflow($item->check_interval_months)->toDateString()
                : null;
        });
    }

    protected function casts(): array
    {
        return [
            'area' => EquipmentArea::class,
            'quantity' => 'integer',
            'available' => 'boolean',
            'attention' => 'boolean',
            'expires_on' => 'date',
            'check_interval_months' => 'integer',
            'last_checked_at' => 'datetime',
            'next_check_on' => 'date',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return HasMany<EquipmentCheck, $this> */
    public function checks(): HasMany
    {
        return $this->hasMany(EquipmentCheck::class)->latest('checked_at');
    }

    public function status(): EquipmentStatus
    {
        $today = today();

        return match (true) {
            ! $this->available => EquipmentStatus::Missing,
            $this->expires_on !== null && $this->expires_on->lt($today) => EquipmentStatus::Expired,
            $this->attention => EquipmentStatus::NeedsAttention,
            $this->next_check_on !== null && $this->next_check_on->lte($today) => EquipmentStatus::CheckDue,
            $this->expires_on !== null && $this->expires_on->lte($today->copy()->addDays(self::EXPIRES_SOON_DAYS)) => EquipmentStatus::ExpiresSoon,
            default => EquipmentStatus::Ok,
        };
    }

    /** Same order as status(): an item matches only its most urgent status. */
    public function scopeWithStatus(Builder $query, EquipmentStatus $status): void
    {
        $today = today()->toDateString();
        $soon = today()->addDays(self::EXPIRES_SOON_DAYS)->toDateString();

        $notExpired = fn (Builder $q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', $today);
        $notDue = fn (Builder $q) => $q->whereNull('next_check_on')->orWhereDate('next_check_on', '>', $today);

        match ($status) {
            EquipmentStatus::Missing => $query->where('available', false),
            EquipmentStatus::Expired => $query->where('available', true)->whereDate('expires_on', '<', $today),
            EquipmentStatus::NeedsAttention => $query->where('available', true)->where($notExpired)->where('attention', true),
            EquipmentStatus::CheckDue => $query->where('available', true)->where($notExpired)->where('attention', false)
                ->whereDate('next_check_on', '<=', $today),
            EquipmentStatus::ExpiresSoon => $query->where('available', true)->where('attention', false)->where($notDue)
                ->whereDate('expires_on', '>=', $today)->whereDate('expires_on', '<=', $soon),
            EquipmentStatus::Ok => $query->where('available', true)->where('attention', false)->where($notDue)
                ->where(fn (Builder $q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>', $soon)),
        };
    }

    /**
     * Record a check. "OK" and "replaced" mean the item is there and fine;
     * "needs attention" keeps it flagged until a later check says otherwise.
     */
    public function recordCheck(CheckResult $result, ?User $user, ?string $note = null, ?string $newExpiry = null): EquipmentCheck
    {
        $check = $this->checks()->create([
            'user_id' => $user?->id,
            'result' => $result,
            'note' => $note,
            'checked_at' => now(),
        ]);

        $this->forceFill([
            'last_checked_at' => $check->checked_at,
            'attention' => $result === CheckResult::NeedsAttention,
            'available' => $result === CheckResult::NeedsAttention ? $this->available : true,
        ]);

        if ($result === CheckResult::Replaced && $newExpiry !== null) {
            $this->expires_on = $newExpiry;
        }

        $this->save();

        return $check;
    }
}
