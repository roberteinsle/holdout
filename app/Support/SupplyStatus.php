<?php

namespace App\Support;

use App\Models\Household;
use App\Registries\StockAmount;
use App\Registries\StockRegistry;
use Illuminate\Support\Collection;

/**
 * Stock against target per category (targets from SupplyTargets, stock from the
 * StockRegistry), and how many days the whole supply lasts: as long as the
 * category that runs out first. Expired stock is shown but never counted.
 */
class SupplyStatus
{
    public function __construct(
        private SupplyTargets $targets,
        private StockRegistry $stock,
    ) {}

    /** @return Collection<string, CategoryStatus> keyed by category key, in display order */
    public function for(Household $household): Collection
    {
        $stock = $this->stock->for($household);

        return $this->targets->for($household)->map(function (SupplyTarget $target, string $key) use ($stock): CategoryStatus {
            $amount = $stock[$key] ?? new StockAmount;

            return new CategoryStatus($target, $amount->usable, $amount->expired);
        });
    }

    /**
     * Whole days the supply lasts: the smallest number over all categories
     * with a target. 0 for a household without members.
     *
     * @param  Collection<string, CategoryStatus>  $status
     */
    public static function daysOfSupply(Collection $status): int
    {
        $relevant = $status->filter(fn (CategoryStatus $category): bool => $category->target->perDay > 0);

        return $relevant->isEmpty() ? 0 : (int) $relevant->min(fn (CategoryStatus $category): int => $category->days());
    }

    /**
     * The category that runs out first.
     *
     * @param  Collection<string, CategoryStatus>  $status
     */
    public static function limitedBy(Collection $status): ?CategoryStatus
    {
        return $status->filter(fn (CategoryStatus $category): bool => $category->target->perDay > 0)
            ->sortBy(fn (CategoryStatus $category): int => $category->days())
            ->first();
    }
}
