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
        Schema::table('bank_transactions', function (Blueprint $table) {
            // Vérifier et créer les index seulement s'ils n'existent pas
            if (!$this->indexExists('bank_transactions', 'bank_transactions_description_index')) {
                $table->index('description');
            }

            if (!$this->indexExists('bank_transactions', 'bank_transactions_bank_account_id_transaction_date_index')) {
                $table->index(['bank_account_id', 'transaction_date']);
            }

            if (!$this->indexExists('bank_transactions', 'bank_transactions_money_category_id_transaction_date_index')) {
                $table->index(['money_category_id', 'transaction_date']);
            }

            if (!$this->indexExists('bank_transactions', 'bank_transactions_transaction_date_index')) {
                $table->index('transaction_date');
            }

            if (!$this->indexExists('bank_transactions', 'bank_transactions_bank_account_id_money_category_id_transaction_date_index')) {
                $table->index(['bank_account_id', 'money_category_id', 'transaction_date']);
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::select("SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $index]) !== [];
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropIndex(['description']);
            $table->dropIndex(['bank_account_id', 'transaction_date']);
            $table->dropIndex(['money_category_id', 'transaction_date']);
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['bank_account_id', 'money_category_id', 'transaction_date']);
        });
    }
};
