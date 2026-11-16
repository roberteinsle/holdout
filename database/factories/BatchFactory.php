<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 6),
            'best_before' => today()->addDays(fake()->numberBetween(30, 720)),
            'note' => null,
        ];
    }
}
