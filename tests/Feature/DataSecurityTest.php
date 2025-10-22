<?php

namespace Tests\Feature;

use App\Models\MoneyCategory;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransactionCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_only_see_their_own_categories()
    {
        // Create two users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create categories for each user
        $user1Category = MoneyCategory::factory()->create(['user_id' => $user1->id, 'name' => 'User 1 Category']);
        $user2Category = MoneyCategory::factory()->create(['user_id' => $user2->id, 'name' => 'User 2 Category']);

        // Test as user 1
        $this->actingAs($user1);
        $user1Categories = MoneyCategory::all();
        $this->assertCount(1, $user1Categories);
        $this->assertEquals('User 1 Category', $user1Categories->first()->name);

        // Test as user 2
        $this->actingAs($user2);
        $user2Categories = MoneyCategory::all();
        $this->assertCount(1, $user2Categories);
        $this->assertEquals('User 2 Category', $user2Categories->first()->name);
    }

    public function test_users_can_only_see_their_own_wallets()
    {
        // Create two users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create wallets for each user
        $user1Wallet = Wallet::factory()->create(['user_id' => $user1->id, 'name' => 'User 1 Wallet']);
        $user2Wallet = Wallet::factory()->create(['user_id' => $user2->id, 'name' => 'User 2 Wallet']);

        // Test as user 1
        $this->actingAs($user1);
        $user1Wallets = Wallet::all();
        $this->assertCount(1, $user1Wallets);
        $this->assertEquals('User 1 Wallet', $user1Wallets->first()->name);

        // Test as user 2
        $this->actingAs($user2);
        $user2Wallets = Wallet::all();
        $this->assertCount(1, $user2Wallets);
        $this->assertEquals('User 2 Wallet', $user2Wallets->first()->name);
    }

    public function test_transaction_cache_service_filters_by_user()
    {
        // Create two users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create categories for each user
        MoneyCategory::factory()->create(['user_id' => $user1->id, 'name' => 'User 1 Category']);
        MoneyCategory::factory()->create(['user_id' => $user2->id, 'name' => 'User 2 Category']);

        $cacheService = app(TransactionCacheService::class);

        // Test cache for user 1
        $user1Categories = $cacheService->getCategories($user1);
        $this->assertCount(1, $user1Categories);
        $this->assertEquals('User 1 Category', $user1Categories->first()->name);

        // Test cache for user 2
        $user2Categories = $cacheService->getCategories($user2);
        $this->assertCount(1, $user2Categories);
        $this->assertEquals('User 2 Category', $user2Categories->first()->name);
    }

    public function test_global_scopes_work_without_authentication()
    {
        // Create users first
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create categories without authentication
        $category1 = MoneyCategory::factory()->create(['user_id' => $user1->id, 'name' => 'Category 1']);
        $category2 = MoneyCategory::factory()->create(['user_id' => $user2->id, 'name' => 'Category 2']);

        // Without authentication, should see all categories
        $allCategories = MoneyCategory::withoutGlobalScopes()->get();
        $this->assertCount(2, $allCategories);

        // With global scopes but no auth, should see all categories
        $categoriesWithScopes = MoneyCategory::get();
        $this->assertCount(2, $categoriesWithScopes);
    }
}
