<?php

namespace App\Services;

use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class WalletUpdateService
{
    /**
     * Update wallet balance when a transaction is categorized
     */
    public function updateWalletFromTransaction(BankTransactions $transaction): void
    {
        // Only process if transaction has a category
        if (!$transaction->money_category_id) {
            return;
        }

        // Get the category
        $category = $transaction->category;
        if (!$category) {
            return;
        }

        // Check if category is linked to a wallet
        $wallet = $category->wallet;
        if (!$wallet) {
            return;
        }

        // Only update single mode wallets
        if (!$wallet->isSingleMode()) {
            Log::info("Wallet {$wallet->id} is in multi mode, skipping automatic balance update");
            return;
        }

        // Update wallet balance
        $this->updateWalletBalance($wallet, $transaction);
    }

    /**
     * Update wallet balance based on transaction amount
     */
    private function updateWalletBalance(Wallet $wallet, BankTransactions $transaction): void
    {
        $currentBalance = (float) $wallet->balance;
        $transactionAmount = (float) $transaction->amount;

        // Add transaction amount to wallet balance (the inverse of the transaction amount)
        $newBalance = $currentBalance - $transactionAmount;

        // Update wallet balance
        $wallet->updateBalance($newBalance);

        Log::info("Updated wallet {$wallet->id} balance from {$currentBalance} to {$newBalance} (transaction: {$transactionAmount})");
    }

    /**
     * Process all uncategorized transactions for wallet-linked categories
     */
    public function processAllUncategorizedTransactions(): int
    {
        $processedCount = 0;

        // Get all transactions that have categories linked to single mode wallets
        $transactions = BankTransactions::whereHas('category.wallet', function ($query) {
            $query->where('mode', 'single');
        })->get();

        foreach ($transactions as $transaction) {
            $this->updateWalletFromTransaction($transaction);
            $processedCount++;
        }

        Log::info("Processed {$processedCount} transactions for wallet updates");

        return $processedCount;
    }

    /**
     * Recalculate wallet balance from all its linked transactions
     */
    public function recalculateWalletBalance(Wallet $wallet): void
    {
        if (!$wallet->isSingleMode()) {
            return;
        }

        // Get all transactions linked to this wallet's category
        $transactions = BankTransactions::where('money_category_id', $wallet->category_linked_id)->get();

        $totalAmount = $transactions->sum('amount');

        // Update wallet balance
        $wallet->updateBalance($totalAmount);

        Log::info("Recalculated wallet {$wallet->id} balance to {$totalAmount} from {$transactions->count()} transactions");
    }

    /**
     * Handle transaction category change
     */
    public function handleCategoryChange(BankTransactions $transaction, ?MoneyCategory $oldCategory, MoneyCategory $newCategory): void
    {
        // If old category was linked to a wallet, we might need to adjust it
        if ($oldCategory && $oldCategory->wallet && $oldCategory->wallet->isSingleMode()) {
            $this->recalculateWalletBalance($oldCategory->wallet);
        }

        // If new category is linked to a wallet, update it
        if ($newCategory->wallet && $newCategory->wallet->isSingleMode()) {
            $this->updateWalletBalance($newCategory->wallet, $transaction);
        }
    }
}
