<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batches: how many packages of a product are in stock, bought together and
     * sharing one best-before date. Locations and movements follow.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('best_before')->nullable();
            $table->string('note', 150)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'best_before']);
            $table->index('best_before');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
