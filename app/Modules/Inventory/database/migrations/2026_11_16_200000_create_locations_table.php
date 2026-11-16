<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storage locations of a household (cellar, kitchen cupboard, ...). A batch
     * may name one; deleting a location keeps its batches without a location.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('note', 150)->nullable();
            $table->timestamps();

            $table->unique(['household_id', 'name']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::dropIfExists('locations');
    }
};
