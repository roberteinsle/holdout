<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\ExpiryStatus;
use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A number of packages of one product with one best-before date. The household
 * is the product's; product_id and location_id are set by the form (checked
 * against the household).
 */
#[Fillable(['product_id', 'location_id', 'quantity', 'best_before', 'note'])]
class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    protected static function newFactory(): BatchFactory
    {
        return BatchFactory::new();
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'best_before' => 'date',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @param  Builder<Batch>  $query */
    public function scopeForHousehold(Builder $query, int $householdId): void
    {
        $query->whereHas('product', fn (Builder $product) => $product->where('household_id', $householdId));
    }

    /** Filter by expiry status, relative to today and the "eat soon" window. */
    public function scopeWithStatus(Builder $query, ExpiryStatus $status, int $eatSoonDays): void
    {
        $today = today()->toDateString();
        $soon = today()->addDays($eatSoonDays)->toDateString();

        match ($status) {
            ExpiryStatus::Expired => $query->whereDate('best_before', '<', $today),
            ExpiryStatus::EatSoon => $query->whereDate('best_before', '>=', $today)->whereDate('best_before', '<=', $soon),
            ExpiryStatus::Fresh => $query->whereDate('best_before', '>', $soon),
            ExpiryStatus::NoDate => $query->whereNull('best_before'),
        };
    }

    /** Earliest best-before first, batches without a date last. */
    public function scopeOrderByBestBefore(Builder $query, string $direction = 'asc'): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->orderByRaw('best_before is null')->orderBy('best_before', $direction);
    }

    public function status(int $eatSoonDays): ExpiryStatus
    {
        return ExpiryStatus::for($this->best_before, $eatSoonDays);
    }

    /** e.g. "3 × 500 g". */
    public function quantityLabel(): string
    {
        return $this->quantity.' × '.$this->product->packageLabel();
    }
}
