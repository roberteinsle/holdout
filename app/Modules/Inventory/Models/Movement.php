<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Inventory\Enums\MovementType;
use Database\Factories\MovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of the stock history. quantity is the change in packages:
 * positive when added, negative when taken out. Written only by the StockLedger
 * and the BatchObserver, never by a form.
 */
#[Fillable(['product_id', 'batch_id', 'user_id', 'type', 'quantity', 'note'])]
class Movement extends Model
{
    /** @use HasFactory<MovementFactory> */
    use HasFactory;

    protected static function newFactory(): MovementFactory
    {
        return MovementFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Batch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<Movement>  $query */
    public function scopeForHousehold(Builder $query, int $householdId): void
    {
        $query->whereHas('product', fn (Builder $product) => $product->where('household_id', $householdId));
    }

    /** e.g. "+4 × 500 g" or "−2 × 500 g" (with a real minus sign). */
    public function quantityLabel(): string
    {
        return ($this->quantity < 0 ? '−' : '+').abs($this->quantity).' × '.$this->product->packageLabel();
    }
}
