<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->index('bank_account_id');
            $table->index('money_category_id');
            $table->index('gocardless_transaction_id');
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropIndex(['bank_account_id']);
            $table->dropIndex(['money_category_id']);
            $table->dropIndex(['gocardless_transaction_id']);
            $table->dropIndex(['transaction_date']);
        });
    }
};
