<?php

namespace Tests\Feature\Services;

use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\User;
use App\Services\TransactionCacheService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCacheServiceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_optimized_transaction_query_applies_filters(): void
    {
        $service = new TransactionCacheService();
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $account1 = BankAccount::factory()->for($user)->create();
        $account2 = BankAccount::factory()->for($user)->create();
        $category = MoneyCategory::factory()->for($user)->create();

        // Seed transactions across accounts, categories, and dates
        BankTransactions::factory()->create([
            'bank_account_id' => $account1->id,
            'description' => 'Grocery market',
            'money_category_id' => $category->id,
            'transaction_date' => Carbon::parse('2024-01-10'),
        ]);
        BankTransactions::factory()->create([
            'bank_account_id' => $account2->id,
            'description' => 'Online subscription',
            'money_category_id' => null,
            'transaction_date' => Carbon::parse('2024-02-15'),
        ]);

        // Filter by account 1 via providing selected account
        $query = $service->getOptimizedTransactionQuery($user, $account1, []);
        $this->assertEquals(1, $query->count());

        // Filter by search term (case-insensitive)
        $query = $service->getOptimizedTransactionQuery($user, null, ['search' => 'gRoCeRy']);
        $this->assertEquals(1, $query->count());

        // Filter by category id
        $query = $service->getOptimizedTransactionQuery($user, null, ['category' => $category->id]);
        $this->assertEquals(1, $query->count());

        // Filter by date range
        $query = $service->getOptimizedTransactionQuery($user, null, [
            'date_range' => [Carbon::parse('2024-01-01'), Carbon::parse('2024-01-31')],
        ]);
        $this->assertEquals(1, $query->count());
    }
}
