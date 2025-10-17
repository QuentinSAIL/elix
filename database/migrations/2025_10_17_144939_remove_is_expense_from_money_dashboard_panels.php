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
        Schema::table('money_dashboard_panels', function (Blueprint $table) {
            $table->dropColumn('is_expense');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('money_dashboard_panels', function (Blueprint $table) {
            $table->boolean('is_expense')->default(true);
        });
    }
};
