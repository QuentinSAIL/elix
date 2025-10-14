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
}
