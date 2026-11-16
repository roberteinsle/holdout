<?php

namespace App\Modules\Inventory\Printout;

use App\Models\Household;
use App\Modules\Inventory\Models\Batch;
use App\Registries\PrintSection;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;

/**
 * Printout: every batch, grouped by storage location (A–Z, "No location" last),
 * by product name and best-before date, with an empty column to tally what is taken.
 */
class StockSection implements PrintSection
{
    public function sort(): int
    {
        return 30;
    }

    public function render(Household $household): ?Renderable
    {
        /** @var Collection<int, Batch> $batches */
        $batches = Batch::query()
            ->forHousehold($household->id)
            ->with(['product.category', 'location'])
            ->get()
            ->sortBy([
                fn (Batch $a, Batch $b): int => strnatcasecmp($a->product->name, $b->product->name),
                fn (Batch $a, Batch $b): int => ($a->best_before?->timestamp ?? PHP_INT_MAX) <=> ($b->best_before?->timestamp ?? PHP_INT_MAX),
            ]);

        $groups = $batches
            ->groupBy(fn (Batch $batch): string => $batch->location?->name ?? '')
            ->sortKeysUsing(fn (string $a, string $b): int => match (true) {
                $a === '' => 1,
                $b === '' => -1,
                default => strnatcasecmp($a, $b),
            });

        return view('inventory::printout.stock', [
            'groups' => $groups,
            'eatSoonDays' => $household->eat_soon_days,
        ]);
    }
}
