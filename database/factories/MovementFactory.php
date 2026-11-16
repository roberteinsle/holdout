<?php

namespace Database\Factories;

use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\Movement;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movement>
 */
class MovementFactory extends Factory
{
    protected $model = Movement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'batch_id' => null,
            'user_id' => null,
            'type' => MovementType::Consumed,
            'quantity' => -1,
            'note' => null,
        ];
    }
}
