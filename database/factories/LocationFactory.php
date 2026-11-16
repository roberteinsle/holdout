<?php

namespace Database\Factories;

use App\Models\Household;
use App\Modules\Inventory\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => fake()->unique()->words(2, true),
            'note' => null,
        ];
    }
}
