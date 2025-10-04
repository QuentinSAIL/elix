<?php

namespace Tests\Unit\Services;

use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @covers \App\Services\WalletUpdateService
 */
class WalletUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletUpdateService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WalletUpdateService();
        $this->user = User::factory()->create();
        Log::swap($this->createMock(\Psr\Log\LoggerInterface::class)); // Mock logger
    }

    #[test]
    public function update_wallet_from_transaction_does_nothing_if_no_category()
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'single', 'balance' => 100]);
        $transaction = BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => null, 'amount' => 50]);

        $this->service->updateWalletFromTransaction($transaction);

        $this->assertEquals(100, $wallet->fresh()->balance);
    }

    #[test]
    public function update_wallet_from_transaction_does_nothing_if_category_not_linked_to_wallet()
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'single', 'balance' => 100]);
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => null]);
        $transaction = BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category->id, 'amount' => 50]);

        $this->service->updateWalletFromTransaction($transaction);

        $this->assertEquals(100, $wallet->fresh()->balance);
    }

    #[test]
    public function update_wallet_from_transaction_does_nothing_if_wallet_is_multi_mode()
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'multi', 'balance' => 100]);
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => $wallet->id]);
        $transaction = BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category->id, 'amount' => 50]);

        $this->service->updateWalletFromTransaction($transaction);

        $this->assertEquals(100, $wallet->fresh()->balance);
    }

    #[test]
    public function update_wallet_from_transaction_updates_balance_for_single_mode_wallet()
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'single', 'balance' => 100]);
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => $wallet->id]);
        $transaction = BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category->id, 'amount' => 50]);

        $this->service->updateWalletFromTransaction($transaction);

        $this->assertEquals(50, $wallet->fresh()->balance); // 100 - 50 = 50
    }

    #[test]
    public function process_all_uncategorized_transactions_processes_linked_transactions()
    {
        $wallet1 = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'single', 'balance' => 100]);
        $category1 = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => $wallet1->id]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category1->id, 'amount' => 20]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category1->id, 'amount' => 30]);

        $wallet2 = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'single', 'balance' => 200]);
        $category2 = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => $wallet2->id]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category2->id, 'amount' => 10]);

        // Unlinked transaction
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => null, 'amount' => 5]);

        $processedCount = $this->service->processAllUncategorizedTransactions();

        $this->assertEquals(3, $processedCount);
        $this->assertEquals(50, $wallet1->fresh()->balance); // 100 - 20 - 30 = 50
        $this->assertEquals(190, $wallet2->fresh()->balance); // 200 - 10 = 190
    }

    #[test]
    public function recalculate_wallet_balance_does_nothing_if_multi_mode()
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'multi', 'balance' => 100]);
        $this->service->recalculateWalletBalance($wallet);
        $this->assertEquals(100, $wallet->fresh()->balance);
    }

    #[test]
    public function recalculate_wallet_balance_recalculates_for_single_mode_wallet()
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'single', 'balance' => 100]);
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => $wallet->id]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category->id, 'amount' => 20]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category->id, 'amount' => 30]);

        $this->service->recalculateWalletBalance($wallet);

        $this->assertEquals(50, $wallet->fresh()->balance); // 20 + 30 = 50
    }

    #[test]
    public function handle_category_change_recalculates_old_wallet_and_updates_new_wallet()
    {
        $oldWallet = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'single', 'balance' => 100]);
        $oldCategory = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => $oldWallet->id]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $oldCategory->id, 'amount' => 20]);

        $newWallet = Wallet::factory()->create(['user_id' => $this->user->id, 'mode' => 'single', 'balance' => 50]);
        $newCategory = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => $newWallet->id]);

        $transaction = BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $oldCategory->id, 'amount' => 30]);

        $this->service->handleCategoryChange($transaction, $oldCategory, $newCategory);

        // Old wallet should be recalculated (100 - 20 = 80, then transaction removed so 100 - 20 = 80)
        $this->assertEquals(20, $oldWallet->fresh()->balance); // Only the remaining transaction (20) should be there

        // New wallet should be updated (50 - 30 = 20)
        $this->assertEquals(20, $newWallet->fresh()->balance);
    }
}
