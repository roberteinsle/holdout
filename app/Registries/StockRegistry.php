<?php

namespace App\Registries;

use App\Models\Household;

/**
 * Modules register where the stock comes from; core features such as the
 * supply status ask the registry and never a module directly.
 */
class StockRegistry
{
    /** @var array<string, StockSource> module => source */
    private array $sources = [];

    public function register(string $module, StockSource $source): void
    {
        $this->sources[$module] = $source;
    }

    /** @return array<string, StockAmount> category key => amount, summed over all sources */
    public function for(Household $household): array
    {
        $total = [];

        foreach ($this->sources as $source) {
            foreach ($source->stockByCategory($household) as $key => $amount) {
                $total[$key] = isset($total[$key]) ? $total[$key]->plus($amount) : $amount;
            }
        }

        return $total;
    }
}
