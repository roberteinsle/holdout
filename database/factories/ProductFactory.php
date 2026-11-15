<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Household;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'category_id' => fn () => Category::query()->inRandomOrder()->value('id'),
            'name' => fake()->words(2, true),
            'brand' => fake()->optional()->company(),
            'package_value' => 500,
            'package_unit' => PackageUnit::Gram,
        ];
    }
}
