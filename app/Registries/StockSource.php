<?php

namespace App\Registries;

use App\Models\Household;

/** A module that knows what a household has in stock (registered in the StockRegistry). */
interface StockSource
{
    /** @return array<string, StockAmount> category key => amount in the category unit (kg or l) */
    public function stockByCategory(Household $household): array;
}
