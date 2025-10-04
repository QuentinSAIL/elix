<?php

namespace Tests\Feature\Models;

use App\Models\MoneyCategory;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $wallet->user);
        $this->assertEquals($user->id, $wallet->user->id);
    }

    public function test_wallet_belongs_to_category(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create(['user_id' => $user->id]);
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'category_linked_id' => $category->id,
        ]);

        $this->assertInstanceOf(MoneyCategory::class, $wallet->category);
        $this->assertEquals($category->id, $wallet->category->id);
    }

    public function test_wallet_has_many_positions(): void
    {
        $wallet = Wallet::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $wallet->positions);
    }

    public function test_wallet_can_be_created(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Wallet',
            'mode' => 'single',
            'balance' => '1000.00',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'user_id' => $user->id,
            'name' => 'Test Wallet',
            'mode' => 'single',
            'balance' => '1000.00',
        ]);
    }

    public function test_wallet_has_correct_fillable_attributes(): void
    {
        $wallet = new Wallet();

        $expectedFillable = [
            'id',
            'user_id',
            'name',
            'unit',
            'mode',
            'balance',
            'category_linked_id',
            'created_at',
            'updated_at',
        ];

        $this->assertEquals($expectedFillable, $wallet->getFillable());
    }

    public function test_wallet_has_correct_casts(): void
    {
        $wallet = new Wallet();

        $expectedCasts = [
            'id' => 'string',
            'balance' => 'decimal:18',
            'mode' => 'string',
        ];

        $this->assertEquals($expectedCasts, $wallet->getCasts());
    }

    public function test_wallet_can_get_current_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => '1000.00']);

        $currentBalance = $wallet->getCurrentBalance();

        $this->assertEquals(1000.0, $currentBalance);
    }

    public function test_wallet_can_calculate_balance_from_positions(): void
    {
        $wallet = Wallet::factory()->create(['mode' => 'multi']);

        // Create some positions
        WalletPosition::factory()->count(2)->create([
            'wallet_id' => $wallet->id,
            'quantity' => '10.00',
            'price' => '50.00',
            'ticker' => null,
        ]);

        $balanceFromPositions = $wallet->calculateBalanceFromPositions();

        $this->assertEquals(1000.0, $balanceFromPositions);
    }

    public function test_wallet_can_update_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => '1000.00']);

        $wallet->updateBalance(1500.00);

        $this->assertEquals('1500.000000000000000000', $wallet->fresh()->balance);
    }

    public function test_wallet_can_update_balance_in_single_mode(): void
    {
        $wallet = Wallet::factory()->create(['balance' => '1000.00', 'mode' => 'single']);

        $wallet->updateBalance(1500.00);

        $this->assertEquals('1500.000000000000000000', $wallet->fresh()->balance);
    }

    public function test_wallet_does_not_update_balance_in_multi_mode(): void
    {
        $wallet = Wallet::factory()->create(['balance' => '1000.00', 'mode' => 'multi']);

        $wallet->updateBalance(1500.00);

        // Balance should remain unchanged in multi mode
        $this->assertEquals('1000.000000000000000000', $wallet->fresh()->balance);
    }

    public function test_wallet_can_check_if_single_mode(): void
    {
        $wallet = Wallet::factory()->create(['mode' => 'single']);

        $this->assertTrue($wallet->isSingleMode());
        $this->assertFalse($wallet->isMultiMode());
    }

    public function test_wallet_can_check_if_multi_mode(): void
    {
        $wallet = Wallet::factory()->create(['mode' => 'multi']);

        $this->assertTrue($wallet->isMultiMode());
        $this->assertFalse($wallet->isSingleMode());
    }

    public function test_wallet_auto_creates_category_on_creation(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Wallet',
            'category_linked_id' => null,
        ]);

        // Should auto-create a category
        $this->assertNotNull($wallet->fresh()->category_linked_id);
        $this->assertInstanceOf(MoneyCategory::class, $wallet->category);
    }
}
