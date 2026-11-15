<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Registries\CategoryDefinition;
use App\Registries\CategoryRegistry;
use Illuminate\Database\Seeder;

/**
 * Sync the categories table with what the modules register. Idempotent: it runs
 * on every container start, so updated values arrive with an update.
 * Categories that no module registers any more are kept (products may use them).
 */
class CategorySeeder extends Seeder
{
    public function run(CategoryRegistry $registry): void
    {
        $registry->all()->each(function (CategoryDefinition $definition) use ($registry): void {
            Category::query()->updateOrCreate(['key' => $definition->key], [
                'module' => $registry->moduleOf($definition->key),
                'name' => $definition->name,
                'need_unit' => $definition->unit,
                'need_per_adult_day' => $definition->perAdultDay,
                'need_per_child_day' => $definition->perChild(),
                'is_water' => $definition->isWater,
                'sort' => $definition->sort,
                'source' => $definition->source,
            ]);
        });
    }
}
