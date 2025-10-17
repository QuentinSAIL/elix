<?php

namespace Tests\Feature\Livewire\Money;

use App\Livewire\Money\WalletIndex;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WalletIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_index_can_be_rendered()
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(WalletIndex::class)
            ->assertStatus(200);
    }

    public function test_loads_wallets_on_mount()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Wallet::factory()->for($user)->count(3)->create();

        Livewire::test(WalletIndex::class)
            ->assertViewHas('wallets', function ($wallets) {
                return count($wallets) === 3;
            });
    }

    public function test_refreshes_wallet_list_on_event()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Wallet::factory()->for($user)->create();

        $component = Livewire::test(WalletIndex::class);

        Wallet::factory()->for($user)->create();

        $component->dispatch('wallets-updated')
            ->assertViewHas('wallets', function ($wallets) {
                return count($wallets) === 2;
            });
    }

    public function test_can_delete_wallet()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletIndex::class)
            ->call('delete', $wallet->id);

        $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
    }

    public function test_get_currency_symbol()
    {
        $user = User::factory()->create();
        $user->preference()->create(['currency' => 'USD']);
        $this->actingAs($user);

        Livewire::test(WalletIndex::class)
            ->assertSet('userCurrency', 'USD');
    }

    public function test_get_total_portfolio_value()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Wallet::factory()->for($user)->count(2)->create(['balance' => 1000]);

        $component = Livewire::test(WalletIndex::class);
        $totalValue = $component->instance()->getTotalPortfolioValue();

        $this->assertIsFloat($totalValue);
    }

    public function test_can_delete_wallet_with_positions()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $wallet->positions()->create([
            'name' => 'Bitcoin',
            'ticker' => 'BTC',
            'quantity' => 1,
            'price' => 50000,
        ]);
        $this->actingAs($user);

        Livewire::test(WalletIndex::class)
            ->call('delete', $wallet->id);

        $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
    }

    public function test_can_delete_nonexistent_wallet()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(WalletIndex::class)
            ->call('delete', '00000000-0000-0000-0000-000000000000')
            ->assertStatus(200);
    }

    public function test_get_wallet_balance_in_currency_single_mode()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create([
            'mode' => 'single',
            'balance' => 1000,
        ]);
        $this->actingAs($user);

        $component = Livewire::test(WalletIndex::class);
        $balance = $component->instance()->getWalletBalanceInCurrency($wallet);

        $this->assertEquals(1000.0, $balance);
    }

    public function test_get_wallet_balance_in_currency_multi_mode()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $wallet->positions()->create([
            'name' => 'Bitcoin',
            'ticker' => 'BTC',
            'quantity' => 1,
            'price' => 50000,
        ]);
        $this->actingAs($user);

        $component = Livewire::test(WalletIndex::class);
        $balance = $component->instance()->getWalletBalanceInCurrency($wallet);

        $this->assertIsFloat($balance);
    }

    public function test_get_currency_symbol_for_various_currencies()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $testCases = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CNY' => '¥',
            'XXX' => 'XXX', // Use 3-character currency code
        ];

        foreach ($testCases as $currency => $expectedSymbol) {
            $user->preference()->updateOrCreate([], ['currency' => $currency]);

            $component = Livewire::test(WalletIndex::class);
            $symbol = $component->instance()->getCurrencySymbol();

            $this->assertEquals($expectedSymbol, $symbol);
        }
    }

    public function test_mount_without_user_preference()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(WalletIndex::class)
            ->assertSet('userCurrency', 'EUR');
    }
}
