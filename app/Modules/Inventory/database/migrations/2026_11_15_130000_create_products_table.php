<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products a household keeps in its supply. Batches (amount, best-before
     * date, location) refer to a product.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('barcode', 14)->nullable();
            $table->string('name', 150);
            $table->string('brand', 100)->nullable();
            $table->decimal('package_value', 10, 3);
            $table->string('package_unit', 4);
            $table->timestamp('off_cached_at')->nullable();
            $table->timestamps();

            $table->unique(['household_id', 'barcode']);
            $table->index(['household_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
