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
        Schema::create('money_dashboard_panels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('money_dashboard_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['chart', 'table', 'text'])->default('chart');
            $table->enum('periode_type', ['dates', 'daily', 'weekly', 'monthly', 'yearly'])->default('daily');
            $table->datetime('period_start')->nullable();
            $table->datetime('period_end')->nullable();
            $table->timestamps();
        });

        Schema::create('money_dashboard_panel_bank_accounts', function (Blueprint $table) {
            $table->foreignUuid('money_dashboard_panel_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('bank_account_id')->constrained()->cascadeOnDelete();
            $table->primary(['money_dashboard_panel_id', 'bank_account_id']);
            $table->timestamps();
        });

        Schema::create('money_dashboard_panel_categories', function (Blueprint $table) {
            $table->foreignUuid('money_dashboard_panel_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('money_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['money_dashboard_panel_id', 'money_category_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('money_dashboard_panels');
        Schema::dropIfExists('money_dashboard_panel_bank_accounts');
        Schema::dropIfExists('money_dashboard_panel_categories');
    }
};
