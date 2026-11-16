<?php

namespace App\Support;

use App\Models\Household;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * What is still missing to reach the target per category (SupplyStatus), rounded
 * up to 0.1 kg or l so the household never buys too little. Expired stock is
 * already counted as missing; it is named so it can be replaced.
 */
class ShoppingList
{
    public function __construct(private SupplyStatus $status) {}

    /** @return Collection<string, ShoppingItem> categories with something missing, in display order */
    public function for(Household $household): Collection
    {
        return $this->status->for($household)
            ->filter(fn (CategoryStatus $category): bool => self::roundUp($category->missing()) > 0)
            ->map(fn (CategoryStatus $category): ShoppingItem => new ShoppingItem(
                $category,
                self::roundUp($category->missing()),
                max(0, $household->target_days - $category->days()),
            ));
    }

    /**
     * Plain text to share or copy, one line per category, in the current locale.
     *
     * @param  Collection<string, ShoppingItem>  $items
     */
    public static function text(Collection $items, int $targetDays): string
    {
        $lines = $items->map(fn (ShoppingItem $item): string => '- '.__($item->status->target->category->name).': '.self::format($item->amount, $item->status->target->category->unit));

        return __('Shopping list for :days days', ['days' => $targetDays])."\n".$lines->implode("\n");
    }

    public static function format(float $value, string $unit): string
    {
        return Number::format($value, maxPrecision: 1, locale: app()->getLocale()).' '.$unit;
    }

    /** Up to the next 0.1; tiny float noise (e.g. 3.3000000001) does not add 0.1. */
    public static function roundUp(float $value): float
    {
        return max(0.0, ceil(round($value * 10, 6)) / 10);
    }
}
