<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Equipment and checklist items beyond food (home pharmacy, power outage,
     * hygiene, documents, …) and the log of their checks.
     */
    public function up(): void
    {
        Schema::create('equipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('area', 20);
            $table->string('name', 150);
            // Set for items added from the suggestions, so they are not added twice.
            $table->string('suggestion_key', 50)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->boolean('available')->default(false);
            $table->boolean('attention')->default(false);
            $table->date('expires_on')->nullable();
            $table->unsignedSmallInteger('check_interval_months')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->date('next_check_on')->nullable();
            $table->string('location', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->unique(['household_id', 'suggestion_key']);
            $table->index(['household_id', 'area']);
        });

        Schema::create('equipment_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('result', 20);
            $table->string('note', 255)->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['equipment_item_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_checks');
        Schema::dropIfExists('equipment_items');
    }
};
