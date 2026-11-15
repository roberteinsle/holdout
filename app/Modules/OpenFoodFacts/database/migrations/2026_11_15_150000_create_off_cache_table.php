<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local copy of Open Food Facts answers, so known products also work
     * without internet. found = false remembers "not in Open Food Facts".
     */
    public function up(): void
    {
        Schema::create('off_cache', function (Blueprint $table) {
            $table->string('barcode', 13)->primary();
            $table->boolean('found');
            $table->json('payload')->nullable();
            $table->timestamp('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('off_cache');
    }
};
