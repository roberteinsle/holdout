<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users sign in with passkeys or magic links only, so the password column goes away.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('household_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('role', 16)->default('member')->after('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password');
        });

        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable();
            $table->dropConstrainedForeignId('household_id');
            $table->dropColumn('role');
        });
    }
};
