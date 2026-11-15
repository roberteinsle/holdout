<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Household size by type (adults, children). The supply targets are
     * calculated from these counts and the household's target days.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->unsignedSmallInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['household_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
