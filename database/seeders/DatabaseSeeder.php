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
     * Local demo data: one household with an owner and a member.
     * Sign in with a magic link; with MAIL_MAILER=log the link is in storage/logs.
     */
    public function run(): void
    {
        $household = Household::factory()->create(['name' => 'Demo household', 'locale' => 'de']);

        User::factory()->owner()->for($household)->create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
        ]);

        User::factory()->for($household)->create([
            'name' => 'Member',
            'email' => 'member@example.com',
        ]);
    }
}
