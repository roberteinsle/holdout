<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs on every container start (AUTORUN_LARAVEL_MIGRATION_SEED), so it must be
 * idempotent and must never create demo data. Demo data: DemoSeeder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);
    }
}
