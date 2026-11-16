<?php

namespace App\Modules\Inventory\Models;

use App\Models\Category;
use App\Models\Household;
use App\Modules\Inventory\Enums\PackageUnit;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

#[Fillable(['category_id', 'barcode', 'name', 'brand', 'package_value', 'package_unit', 'image_path', 'image_credit'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public const IMAGE_DISK = 'data';

    public const IMAGE_DIRECTORY = 'product-images';

    /** A replaced or deleted product takes its photo file with it. */
    protected static function booted(): void
    {
        static::updated(function (Product $product): void {
            $old = $product->getOriginal('image_path');

            if ($product->wasChanged('image_path') && $old !== null) {
                Storage::disk(self::IMAGE_DISK)->delete($old);
            }
        });

        static::deleted(function (Product $product): void {
            if ($product->image_path !== null) {
                Storage::disk(self::IMAGE_DISK)->delete($product->image_path);
            }
        });
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected function casts(): array
    {
        return [
            'package_value' => 'float',
            'package_unit' => PackageUnit::class,
            'off_cached_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<Batch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /** @return HasMany<Movement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /** One package in the category unit (kg or l). */
    public function packageInBaseUnit(): float
    {
        return $this->package_value * $this->package_unit->toBaseFactor();
    }

    /** e.g. "500 g" or "1,5 l" in the current locale. */
    public function packageLabel(): string
    {
        return Number::format($this->package_value, maxPrecision: 3, locale: app()->getLocale()).' '.$this->package_unit->value;
    }
}
