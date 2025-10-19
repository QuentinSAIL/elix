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
        // Drop the existing check constraint for type
        DB::statement('ALTER TABLE money_dashboard_panels DROP CONSTRAINT IF EXISTS money_dashboard_panels_type_check');

        // Change the type column to string to allow new values
        Schema::table('money_dashboard_panels', function (Blueprint $table) {
            $table->string('type')->default('bar')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('money_dashboard_panels', function (Blueprint $table) {
            $table->enum('type', ['bar', 'doughnut', 'pie', 'line', 'table', 'number'])->default('bar')->change();
        });
    }
};
