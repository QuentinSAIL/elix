<?php

namespace Tests\Unit\Models;

use App\Models\MoneyCategory;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * @covers \App\Models\Wallet
 */
class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    #[test]
    public function it_belongs_to_a_user()
    {
        $user = Auth::user();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $wallet->user);
        $this->assertEquals($user->id, $wallet->user->id);
    }

    #[test]
    public function it_has_many_positions()
    {
        $wallet = Wallet::factory()->create();
        WalletPosition::factory()->count(3)->for($wallet)->create();

        $this->assertInstanceOf(Collection::class, $wallet->positions);
        $this->assertCount(3, $wallet->positions);
    }

    #[test]
    public function it_has_a_category()
    {
        $wallet = Wallet::factory()->create();

        $this->assertInstanceOf(MoneyCategory::class, $wallet->category);
        $this->assertEquals($wallet->category_linked_id, $wallet->category->id);
    }

    #[test]
    public function it_sets_default_unit_and_mode_on_creating()
    {
        $wallet = Wallet::factory()->create(['unit' => null, 'mode' => null]);

        $this->assertEquals('EUR', $wallet->unit);
        $this->assertEquals('single', $wallet->mode);
    }

    #[test]
    public function it_auto_creates_and_links_a_category_on_created()
    {
        $wallet = Wallet::factory()->create(['category_linked_id' => null]);

        $this->assertNotNull($wallet->category_linked_id);
        $this->assertDatabaseHas('money_categories', [
            'id' => $wallet->category_linked_id,
            'user_id' => $wallet->user_id,
            'name' => 'transfert vers '.$wallet->name,
        ]);
    }

    #[test]
    public function is_single_mode_returns_true_for_single_mode_wallet()
    {
        $wallet = Wallet::factory()->create(['mode' => 'single']);
        $this->assertTrue($wallet->isSingleMode());
        $this->assertFalse($wallet->isMultiMode());
    }

    #[test]
    public function is_multi_mode_returns_true_for_multi_mode_wallet()
    {
        $wallet = Wallet::factory()->create(['mode' => 'multi']);
        $this->assertTrue($wallet->isMultiMode());
        $this->assertFalse($wallet->isSingleMode());
    }

    #[test]
    public function get_current_balance_returns_stored_balance_for_single_mode()
    {
        $wallet = Wallet::factory()->create(['mode' => 'single', 'balance' => 123.45]);
        $this->assertEquals(123.45, $wallet->getCurrentBalance());
    }

    #[test]
    public function get_current_balance_calculates_from_positions_for_multi_mode()
    {
        $wallet = Wallet::factory()->create(['mode' => 'multi']);
        WalletPosition::factory()->for($wallet)->create(['quantity' => 2, 'price' => 50]);
        WalletPosition::factory()->for($wallet)->create(['quantity' => 1, 'price' => 100]);

        // Mock the getCurrentPrice method of WalletPosition
        $this->mock('App\Models\WalletPosition', function ($mock) {
            $mock->shouldReceive('getCurrentPrice')
                ->andReturnUsing(function () {
                    static $prices = [50, 100];

                    return array_shift($prices);
                });
        });

        $this->assertEquals(200.0, $wallet->getCurrentBalance()); // (2*50) + (1*100) = 100 + 100 = 200
    }

    #[test]
    public function calculate_balance_from_positions_returns_stored_balance_if_no_positions()
    {
        $wallet = Wallet::factory()->create(['mode' => 'multi', 'balance' => 50.0]);
        $this->assertEquals(50.0, $wallet->calculateBalanceFromPositions());
    }

    #[test]
    public function calculate_balance_from_positions_calculates_correctly()
    {
        $wallet = Wallet::factory()->create(['mode' => 'multi']);
        WalletPosition::factory()->for($wallet)->create(['quantity' => 2, 'price' => 50]);
        WalletPosition::factory()->for($wallet)->create(['quantity' => 1, 'price' => 100]);

        // Mock the getCurrentPrice method of WalletPosition
        $this->mock('App\Models\WalletPosition', function ($mock) {
            $mock->shouldReceive('getCurrentPrice')
                ->andReturnUsing(function () {
                    static $prices = [50, 100];

                    return array_shift($prices);
                });
        });

        $this->assertEquals(200.0, $wallet->calculateBalanceFromPositions());
    }

    #[test]
    public function update_balance_updates_for_single_mode_wallet()
    {
        $wallet = Wallet::factory()->create(['mode' => 'single', 'balance' => 100.0]);
        $wallet->updateBalance(250.0);
        $this->assertEquals(250.0, $wallet->fresh()->balance);
    }

    #[test]
    public function update_balance_does_not_update_for_multi_mode_wallet()
    {
        $wallet = Wallet::factory()->create(['mode' => 'multi', 'balance' => 100.0]);
        $wallet->updateBalance(250.0);
        $this->assertEquals(100.0, $wallet->fresh()->balance);
    }
}
