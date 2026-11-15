<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Local demo data: one household with two people.
     */
    public function run(): void
    {
        $household = Household::factory()->create(['name' => 'Demo household', 'locale' => 'de']);

        User::factory()->for($household)->create(['name' => 'Alex']);
        User::factory()->for($household)->create(['name' => 'Sam']);
    }
}
