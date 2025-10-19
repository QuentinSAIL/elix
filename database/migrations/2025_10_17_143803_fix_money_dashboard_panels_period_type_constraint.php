<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Force drop the check constraint if it still exists
        DB::statement('ALTER TABLE money_dashboard_panels DROP CONSTRAINT IF EXISTS money_dashboard_panels_period_type_check');

        // Ensure the column is a string type (not enum)
        Schema::table('money_dashboard_panels', function (Blueprint $table) {
            $table->string('period_type')->default('all')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('money_dashboard_panels', function (Blueprint $table) {
            $table->enum('period_type', [
                'all',
                'daily',
                'weekly',
                'biweekly',
                'monthly',
                'quarterly',
                'biannual',
                'yearly',
            ])->default('all')->change();
        });
    }
};
