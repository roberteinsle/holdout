<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('role', 16)->default('member');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['household_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
