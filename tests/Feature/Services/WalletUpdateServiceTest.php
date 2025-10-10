<?php

namespace Tests\Feature\Services;

use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WalletUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletUpdateService $walletUpdateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletUpdateService = new WalletUpdateService;
    }

    public function test_update_wallet_from_transaction_with_category(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '1000',
        ]);

        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        // Link wallet to category
        $wallet->update(['category_linked_id' => $category->id]);

        $transaction = BankTransactions::factory()->create([
            'money_category_id' => $category->id,
            'amount' => '100',
        ]);

        $this->walletUpdateService->updateWalletFromTransaction($transaction);

        $wallet->refresh();
        $this->assertEquals('900.000000000000000000', $wallet->balance);
    }

    public function test_update_wallet_from_transaction_without_category(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '1000',
        ]);

        $transaction = BankTransactions::factory()->create([
            'money_category_id' => null,
            'amount' => '100',
        ]);

        $this->walletUpdateService->updateWalletFromTransaction($transaction);

        $wallet->refresh();
        $this->assertEquals('1000.000000000000000000', $wallet->balance); // Should not change
    }

    public function test_update_wallet_from_transaction_without_wallet_linked_category(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '1000',
        ]);

        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        $transaction = BankTransactions::factory()->create([
            'money_category_id' => $category->id,
            'amount' => '100',
        ]);

        $this->walletUpdateService->updateWalletFromTransaction($transaction);

        $wallet->refresh();
        $this->assertEquals('1000.000000000000000000', $wallet->balance); // Should not change
    }

    public function test_update_wallet_from_transaction_multi_mode_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'multi',
            'balance' => '1000',
        ]);

        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        // Link wallet to category
        $wallet->update(['category_linked_id' => $category->id]);

        $transaction = BankTransactions::factory()->create([
            'money_category_id' => $category->id,
            'amount' => '100',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with("Wallet {$wallet->id} is in multi mode, skipping automatic balance update");

        $this->walletUpdateService->updateWalletFromTransaction($transaction);

        $wallet->refresh();
        $this->assertEquals('1000.000000000000000000', $wallet->balance); // Should not change
    }

    public function test_process_all_uncategorized_transactions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '1000',
        ]);

        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        // Link wallet to category
        $wallet->update(['category_linked_id' => $category->id]);

        BankTransactions::factory()->count(3)->create([
            'money_category_id' => $category->id,
            'amount' => '100',
        ]);

        Log::shouldReceive('info')
            ->atLeast()->once();

        $processedCount = $this->walletUpdateService->processAllUncategorizedTransactions();

        $this->assertEquals(3, $processedCount);
    }

    public function test_recalculate_wallet_balance(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '1000',
            'category_linked_id' => $category->id,
        ]);

        BankTransactions::factory()->count(3)->create([
            'money_category_id' => $category->id,
            'amount' => '100',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with("Recalculated wallet {$wallet->id} balance to 300 from 3 transactions");

        $this->walletUpdateService->recalculateWalletBalance($wallet);

        $wallet->refresh();
        $this->assertEquals('300.000000000000000000', $wallet->balance);
    }

    public function test_recalculate_wallet_balance_multi_mode(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'multi',
            'balance' => '1000',
        ]);

        $this->walletUpdateService->recalculateWalletBalance($wallet);

        $wallet->refresh();
        $this->assertEquals('1000.000000000000000000', $wallet->balance); // Should not change
    }

    public function test_handle_category_change_from_wallet_to_wallet(): void
    {
        $user = User::factory()->create();

        $oldCategory = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        $newCategory = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        $oldWallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '1000',
            'category_linked_id' => $oldCategory->id,
        ]);

        $newWallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '500',
            'category_linked_id' => $newCategory->id,
        ]);

        $transaction = BankTransactions::factory()->create([
            'money_category_id' => $newCategory->id,
            'amount' => '100',
        ]);

        BankTransactions::factory()->create([
            'money_category_id' => $oldCategory->id,
            'amount' => '200',
        ]);

        Log::shouldReceive('info')
            ->twice(); // Once for recalculate, once for update

        $this->walletUpdateService->handleCategoryChange($transaction, $oldCategory, $newCategory);

        $oldWallet->refresh();
        $newWallet->refresh();

        $this->assertEquals('200.000000000000000000', $oldWallet->balance); // Recalculated from transactions
        $this->assertEquals('400.000000000000000000', $newWallet->balance); // Updated with transaction
    }

    public function test_handle_category_change_from_no_wallet_to_wallet(): void
    {
        $user = User::factory()->create();

        $newWallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '500',
        ]);

        $newCategory = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        // Link wallet to category
        $newWallet->update(['category_linked_id' => $newCategory->id]);

        $transaction = BankTransactions::factory()->create([
            'money_category_id' => $newCategory->id,
            'amount' => '100',
        ]);

        Log::shouldReceive('info')
            ->once(); // Only for update

        $this->walletUpdateService->handleCategoryChange($transaction, null, $newCategory);

        $newWallet->refresh();
        $this->assertEquals('400.000000000000000000', $newWallet->balance); // Updated with transaction
    }

    public function test_handle_category_change_to_no_wallet(): void
    {
        $user = User::factory()->create();

        $oldCategory = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        $newCategory = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        $oldWallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '1000',
            'category_linked_id' => $oldCategory->id,
        ]);

        $transaction = BankTransactions::factory()->create([
            'money_category_id' => $newCategory->id,
            'amount' => '100',
        ]);

        BankTransactions::factory()->create([
            'money_category_id' => $oldCategory->id,
            'amount' => '200',
        ]);

        Log::shouldReceive('info')
            ->once(); // Only for recalculate

        $this->walletUpdateService->handleCategoryChange($transaction, $oldCategory, $newCategory);

        $oldWallet->refresh();
        $this->assertEquals('200.000000000000000000', $oldWallet->balance); // Recalculated from transactions
    }

    public function test_logs_wallet_balance_update(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
            'balance' => '1000',
        ]);

        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        // Link wallet to category
        $wallet->update(['category_linked_id' => $category->id]);

        $transaction = BankTransactions::factory()->create([
            'money_category_id' => $category->id,
            'amount' => '100',
        ]);

        Log::shouldReceive('info')
            ->once();

        $this->walletUpdateService->updateWalletFromTransaction($transaction);
    }
}
