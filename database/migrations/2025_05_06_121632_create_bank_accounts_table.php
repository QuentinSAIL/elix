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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('gocardless_account_id')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->date('end_valid_access')->nullable();
            $table->string('institution_id')->nullable();
            $table->string('agreement_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('transaction_total_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
