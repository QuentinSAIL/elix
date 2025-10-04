<?php

namespace Tests\Unit\Services;

use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\User;
use App\Services\TransactionCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * @covers \App\Services\TransactionCacheService
 */
class TransactionCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionCacheService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransactionCacheService();
        $this->user = User::factory()->create();
        Cache::clear(); // Ensure a clean cache for each test
    }

    #[test]
    public function get_user_account_counts_returns_cached_value()
    {
        $cacheKey = 'user_transactions_' . $this->user->id . '_counts';
        Cache::put($cacheKey, ['account-id-1' => 5, 'account-id-2' => 10], 300);

        $counts = $this->service->getUserAccountCounts($this->user);

        $this->assertEquals(['account-id-1' => 5, 'account-id-2' => 10], $counts);
    }

    #[test]
    public function get_user_account_counts_fetches_and_caches_if_not_available()
    {
        $account1 = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $account2 = BankAccount::factory()->create(['user_id' => $this->user->id]);
        BankTransactions::factory()->count(3)->create(['user_id' => $this->user->id, 'bank_account_id' => $account1->id]);
        BankTransactions::factory()->count(2)->create(['user_id' => $this->user->id, 'bank_account_id' => $account2->id]);

        $counts = $this->service->getUserAccountCounts($this->user);

        $this->assertEquals([$account1->id => 3, $account2->id => 2], $counts);
        $this->assertTrue(Cache::has('user_transactions_' . $this->user->id . '_counts'));
    }

    #[test]
    public function get_user_total_count_returns_cached_value()
    {
        $cacheKey = 'user_transactions_' . $this->user->id . '_total';
        Cache::put($cacheKey, 100, 300);

        $total = $this->service->getUserTotalCount($this->user);

        $this->assertEquals(100, $total);
    }

    #[test]
    public function get_user_total_count_fetches_and_caches_if_not_available()
    {
        BankTransactions::factory()->count(7)->create(['user_id' => $this->user->id]);

        $total = $this->service->getUserTotalCount($this->user);

        $this->assertEquals(7, $total);
        $this->assertTrue(Cache::has('user_transactions_' . $this->user->id . '_total'));
    }

    #[test]
    public function get_categories_returns_cached_value()
    {
        $cacheKey = 'categories_all';
        $categories = MoneyCategory::factory()->count(2)->create();
        Cache::put($cacheKey, $categories, 300);

        $fetchedCategories = $this->service->getCategories();

        $this->assertEquals($categories->pluck('id'), $fetchedCategories->pluck('id'));
    }

    #[test]
    public function get_categories_fetches_and_caches_if_not_available()
    {
        MoneyCategory::factory()->count(3)->create();

        $fetchedCategories = $this->service->getCategories();

        $this->assertCount(3, $fetchedCategories);
        $this->assertTrue(Cache::has('categories_all'));
    }

    #[test]
    public function clear_user_cache_clears_user_specific_caches()
    {
        $userCountsCacheKey = 'user_transactions_' . $this->user->id . '_counts';
        $userTotalCacheKey = 'user_transactions_' . $this->user->id . '_total';
        Cache::put($userCountsCacheKey, [], 300);
        Cache::put($userTotalCacheKey, 0, 300);

        $this->service->clearUserCache($this->user);

        $this->assertFalse(Cache::has($userCountsCacheKey));
        $this->assertFalse(Cache::has($userTotalCacheKey));
    }

    #[test]
    public function clear_account_cache_clears_account_and_user_caches()
    {
        $account = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $accountCacheKey = 'account_transactions_' . $account->id;
        $userCountsCacheKey = 'user_transactions_' . $this->user->id . '_counts';
        $userTotalCacheKey = 'user_transactions_' . $this->user->id . '_total';

        Cache::put($accountCacheKey, [], 300);
        Cache::put($userCountsCacheKey, [], 300);
        Cache::put($userTotalCacheKey, 0, 300);

        $this->service->clearAccountCache($account);

        $this->assertFalse(Cache::has($accountCacheKey));
        $this->assertFalse(Cache::has($userCountsCacheKey));
        $this->assertFalse(Cache::has($userTotalCacheKey));
    }

    #[test]
    public function clear_all_caches_flushes_all_cache()
    {
        Cache::put('some_key', 'value', 300);
        Cache::put('another_key', 'value', 300);

        $this->service->clearAllCaches();

        $this->assertFalse(Cache::has('some_key'));
        $this->assertFalse(Cache::has('another_key'));
    }

    #[test]
    public function get_optimized_transaction_query_returns_correct_query_for_user()
    {
        $query = $this->service->getOptimizedTransactionQuery($this->user);

        $this->assertEquals(
            $this->user->bankTransactions()->with(['category', 'account'])->toSql(),
            $query->toSql()
        );
    }

    #[test]
    public function get_optimized_transaction_query_applies_selected_account_filter()
    {
        $account = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $query = $this->service->getOptimizedTransactionQuery($this->user, $account);

        $this->assertEquals(
            $account->transactions()->with(['category', 'account'])->toSql(),
            $query->toSql()
        );
    }

    #[test]
    public function get_optimized_transaction_query_applies_search_filter()
    {
        $filters = ['search' => 'test'];
        $query = $this->service->getOptimizedTransactionQuery($this->user, null, $filters);

        $expectedSql = $this->user->bankTransactions()
            ->whereRaw('LOWER(description) LIKE ?', ['%test%'])
            ->with(['category', 'account'])
            ->toSql();

        $this->assertEquals($expectedSql, $query->toSql());
        $this->assertEquals(['%test%'], $query->getBindings());
    }

    #[test]
    public function get_optimized_transaction_query_applies_category_filter()
    {
        $category = MoneyCategory::factory()->create();
        $filters = ['category' => $category->id];
        $query = $this->service->getOptimizedTransactionQuery($this->user, null, $filters);

        $expectedSql = $this->user->bankTransactions()
            ->where('money_category_id', $category->id)
            ->with(['category', 'account'])
            ->toSql();

        $this->assertEquals($expectedSql, $query->toSql());
        $this->assertEquals([$category->id], $query->getBindings());
    }

    #[test]
    public function get_optimized_transaction_query_applies_date_range_filter()
    {
        $startDate = '2023-01-01';
        $endDate = '2023-01-31';
        $filters = ['date_range' => [$startDate, $endDate]];
        $query = $this->service->getOptimizedTransactionQuery($this->user, null, $filters);

        $expectedSql = $this->user->bankTransactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['category', 'account'])
            ->toSql();

        $this->assertEquals($expectedSql, $query->toSql());
        $this->assertEquals([$startDate, $endDate], $query->getBindings());
    }

    #[test]
    public function warm_up_user_cache_warms_all_relevant_caches()
    {
        $account1 = BankAccount::factory()->create(['user_id' => $this->user->id]);
        BankTransactions::factory()->count(3)->create(['user_id' => $this->user->id, 'bank_account_id' => $account1->id]);
        MoneyCategory::factory()->count(2)->create();

        $this->service->warmUpUserCache($this->user);

        $this->assertTrue(Cache::has('user_transactions_' . $this->user->id . '_counts'));
        $this->assertTrue(Cache::has('user_transactions_' . $this->user->id . '_total'));
        $this->assertTrue(Cache::has('categories_all'));
    }
}
