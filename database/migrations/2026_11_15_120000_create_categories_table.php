<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supply categories, synced from the CategoryRegistry by CategorySeeder
     * (runs on every container start). Products will reference them.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('module', 50);
            $table->string('name');
            $table->string('need_unit', 8);
            $table->decimal('need_per_adult_day', 10, 4);
            $table->decimal('need_per_child_day', 10, 4);
            $table->boolean('is_water')->default(false);
            $table->unsignedSmallInteger('sort')->default(100);
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
