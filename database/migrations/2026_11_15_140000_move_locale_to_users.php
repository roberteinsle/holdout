<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each person chooses their own language (profile); the household no
     * longer has one. Existing people keep the household's language.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('name');
        });

        DB::table('users')->update([
            'locale' => DB::raw('(select locale from households where households.id = users.household_id)'),
        ]);

        Schema::table('households', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->string('locale', 5)->default('en');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
