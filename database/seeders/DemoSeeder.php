<?php

namespace Database\Seeders;

use App\Enums\MemberType;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Local demo data: one household (2 adults, 1 child) with two people using the app.
     * Run with: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $household = Household::factory()->create(['name' => 'Demo household']);
        $household->setMemberCount(MemberType::Adult, 2);
        $household->setMemberCount(MemberType::Child, 1);

        User::factory()->for($household)->create(['name' => 'Alex']);
        User::factory()->for($household)->create(['name' => 'Sam']);
    }
}
