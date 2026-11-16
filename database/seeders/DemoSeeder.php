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
     * Local demo data: one household (2 adults, 1 child) with an owner and a member.
     * Run with: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $household = Household::factory()->create(['name' => 'Demo household']);
        $household->setMemberCount(MemberType::Adult, 2);
        $household->setMemberCount(MemberType::Child, 1);

        // Sign in with an email link; with MAIL_MAILER=log the link is in storage/logs.
        User::factory()->owner()->for($household)->create(['name' => 'Alex', 'email' => 'owner@example.com']);
        User::factory()->for($household)->create(['name' => 'Sam', 'email' => 'member@example.com']);
    }
}
