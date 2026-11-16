<?php

namespace Database\Factories;

use App\Models\Household;
use App\Modules\Equipment\Enums\EquipmentArea;
use App\Modules\Equipment\Models\EquipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentItem>
 */
class EquipmentItemFactory extends Factory
{
    protected $model = EquipmentItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'area' => EquipmentArea::Power,
            'name' => fake()->words(2, true),
            'available' => true,
        ];
    }
}
