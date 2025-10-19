<?php

namespace Tests\Feature\Models;

use App\Models\User;
use App\Models\Wallet;
use App\Models\MoneyCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_wallets(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->wallets);
    }

    public function test_user_has_many_categories(): void
    {
        $user = User::factory()->create();
        MoneyCategory::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->moneyCategories);
    }

    public function test_user_can_get_total_balance(): void
    {
        $user = User::factory()->create();
        \App\Models\BankAccount::factory()->create([
            'user_id' => $user->id,
            'balance' => '1000.00',
        ]);
        \App\Models\BankAccount::factory()->create([
            'user_id' => $user->id,
            'balance' => '500.00',
        ]);

        $totalBalance = $user->sumBalances();

        $this->assertEquals(1500.0, $totalBalance);
    }

    public function test_user_can_get_wallets_count(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->count(3)->create(['user_id' => $user->id]);

        $walletsCount = $user->wallets()->count();

        $this->assertEquals(3, $walletsCount);
    }

    public function test_user_can_get_categories_count(): void
    {
        $user = User::factory()->create();
        MoneyCategory::factory()->count(2)->create(['user_id' => $user->id]);

        $categoriesCount = $user->moneyCategories()->count();

        $this->assertEquals(2, $categoriesCount);
    }

    public function test_user_can_check_if_has_module(): void
    {
        $user = User::factory()->create();
        $module = \App\Models\Module::factory()->create(['name' => 'money']);
        $user->modules()->attach($module);

        $this->assertTrue($user->hasModule('money'));
        $this->assertFalse($user->hasModule('notes'));
    }

    public function test_user_can_check_if_has_api_key(): void
    {
        $user = User::factory()->create();
        $apiService = \App\Models\ApiService::factory()->create();
        \App\Models\ApiKey::factory()->create([
            'user_id' => $user->id,
            'api_service_id' => $apiService->id,
        ]);

        $this->assertTrue($user->hasApiKey($apiService->id));
        
        // Test with a non-existent UUID
        $nonExistentUuid = '0199f930-fb30-71e0-b028-65687c3db4d7';
        $this->assertFalse($user->hasApiKey($nonExistentUuid));
    }
}
