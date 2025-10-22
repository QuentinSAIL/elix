<?php

namespace Tests\Feature\Services;

use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\User;
use App\Services\TransactionCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TransactionCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionCacheService $transactionCacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionCacheService = new TransactionCacheService;
    }

    public function test_can_warm_up_user_cache(): void
    {
        $user = User::factory()->create();

        // Create some test data
        BankAccount::factory()->count(2)->create(['user_id' => $user->id]);
        BankTransactions::factory()->count(5)->create();
        MoneyCategory::factory()->count(3)->create(['user_id' => $user->id]);

        $this->transactionCacheService->warmUpUserCache($user);

        // Check if cache keys exist
        $this->assertTrue(Cache::has("user_transactions_{$user->id}_counts"));
        $this->assertTrue(Cache::has("user_transactions_{$user->id}_total"));
        $this->assertTrue(Cache::has("categories_{$user->id}"));
    }

    public function test_can_get_user_account_counts(): void
    {
        $user = User::factory()->create();

        // Create some accounts
        BankAccount::factory()->count(3)->create(['user_id' => $user->id]);

        $counts = $this->transactionCacheService->getUserAccountCounts($user);

        $this->assertIsArray($counts);
        $this->assertCount(3, $counts);
    }

    public function test_can_get_user_total_count(): void
    {
        $user = User::factory()->create();

        // Create some transactions for this user
        $account = BankAccount::factory()->create(['user_id' => $user->id]);
        BankTransactions::factory()->count(10)->create(['bank_account_id' => $account->id]);

        $totalCount = $this->transactionCacheService->getUserTotalCount($user);

        $this->assertIsInt($totalCount);
        $this->assertEquals(10, $totalCount);
    }

    public function test_can_get_categories(): void
    {
        $user = User::factory()->create();

        // Create some categories
        MoneyCategory::factory()->count(5)->create(['user_id' => $user->id]);

        $categories = $this->transactionCacheService->getCategories($user);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $categories);
        $this->assertCount(5, $categories);
    }

    public function test_can_clear_user_cache(): void
    {
        $user = User::factory()->create();

        // Warm up cache first
        $this->transactionCacheService->warmUpUserCache($user);

        // Verify cache exists
        $this->assertTrue(Cache::has("user_transactions_{$user->id}_counts"));

        // Clear cache
        $this->transactionCacheService->clearUserCache($user);

        // Verify cache is cleared
        $this->assertFalse(Cache::has("user_transactions_{$user->id}_counts"));
    }

    public function test_can_clear_all_cache(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Warm up cache for both users
        $this->transactionCacheService->warmUpUserCache($user1);
        $this->transactionCacheService->warmUpUserCache($user2);

        // Verify cache exists
        $this->assertTrue(Cache::has("user_transactions_{$user1->id}_counts"));
        $this->assertTrue(Cache::has("user_transactions_{$user2->id}_counts"));
        $this->assertTrue(Cache::has("categories_{$user1->id}"));
        $this->assertTrue(Cache::has("categories_{$user2->id}"));

        // Clear all cache
        $this->transactionCacheService->clearAllCaches();

        // Verify cache is cleared
        $this->assertFalse(Cache::has("user_transactions_{$user1->id}_counts"));
        $this->assertFalse(Cache::has("user_transactions_{$user2->id}_counts"));
        $this->assertFalse(Cache::has("categories_{$user1->id}"));
        $this->assertFalse(Cache::has("categories_{$user2->id}"));
    }

    public function test_can_get_cached_user_account_counts(): void
    {
        $user = User::factory()->create();

        // Create some accounts
        BankAccount::factory()->count(2)->create(['user_id' => $user->id]);

        // First call should cache the result
        $counts1 = $this->transactionCacheService->getUserAccountCounts($user);

        // Second call should return cached result
        $counts2 = $this->transactionCacheService->getUserAccountCounts($user);

        $this->assertEquals($counts1, $counts2);
    }

    public function test_can_get_cached_user_total_count(): void
    {
        $user = User::factory()->create();

        // Create some transactions
        BankTransactions::factory()->count(5)->create();

        // First call should cache the result
        $count1 = $this->transactionCacheService->getUserTotalCount($user);

        // Second call should return cached result
        $count2 = $this->transactionCacheService->getUserTotalCount($user);

        $this->assertEquals($count1, $count2);
    }

    public function test_can_get_cached_categories(): void
    {
        $user = User::factory()->create();

        // Create some categories
        MoneyCategory::factory()->count(3)->create(['user_id' => $user->id]);

        // First call should cache the result
        $categories1 = $this->transactionCacheService->getCategories($user);

        // Second call should return cached result
        $categories2 = $this->transactionCacheService->getCategories($user);

        $this->assertEquals($categories1->count(), $categories2->count());
    }

    public function test_can_handle_empty_user_data(): void
    {
        $user = User::factory()->create();

        $counts = $this->transactionCacheService->getUserAccountCounts($user);
        $totalCount = $this->transactionCacheService->getUserTotalCount($user);
        $categories = $this->transactionCacheService->getCategories($user);

        $this->assertIsArray($counts);
        $this->assertEmpty($counts);
        $this->assertEquals(0, $totalCount);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $categories);
        $this->assertEmpty($categories);
    }
}
