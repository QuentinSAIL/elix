<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticker')->unique();
            $table->enum('type', ['CRYPTO', 'TOKEN', 'STOCK', 'COMMODITY', 'ETF', 'BOND', 'OTHER'])->default('OTHER');
            $table->decimal('price', 36, 18)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour les recherches fréquentes
            $table->index(['ticker', 'type']);
            $table->index('last_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_assets');
    }
};
