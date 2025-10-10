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
            $table->string('title')->nullable();
            $table->boolean('is_expense')->default(true);
            $table->enum('type', ['bar', 'doughnut', 'pie', 'line', 'table', 'number'])->default('bar');
            $table->enum('period_type', [
                'all',
                'daily',
                'weekly',
                'biweekly',
                'monthly',
                'quarterly',
                'biannual',
                'yearly',
            ])->default('all');
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
