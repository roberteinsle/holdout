<?php

namespace App\Modules\Inventory\Support;

use App\Models\Household;
use App\Modules\Inventory\Models\Batch;
use App\Registries\StockAmount;
use App\Registries\StockSource;

/**
 * The household's stock for the StockRegistry: packages of all batches times
 * the package size, converted to kg or l (1 l = 1 kg), per category. Batches
 * past their best-before date count as expired, not as usable.
 */
class InventoryStock implements StockSource
{
    public function stockByCategory(Household $household): array
    {
        $today = today();
        $stock = [];

        Batch::query()
            ->forHousehold($household->getKey())
            ->with('product.category')
            ->get()
            ->each(function (Batch $batch) use (&$stock, $today): void {
                $key = $batch->product->category->key;
                $amount = $batch->quantity * $batch->product->packageInBaseUnit();
                $expired = $batch->best_before !== null && $batch->best_before->lt($today);
                $add = $expired ? new StockAmount(expired: $amount) : new StockAmount(usable: $amount);

                $stock[$key] = isset($stock[$key]) ? $stock[$key]->plus($add) : $add;
            });

        return $stock;
    }
}
