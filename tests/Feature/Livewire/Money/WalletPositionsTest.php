<?php

namespace Tests\Feature\Livewire\Money;

use App\Livewire\Money\WalletPositions;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WalletPositionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_positions_on_mount()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        WalletPosition::factory()->for($wallet)->count(3)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->assertViewHas('positions', function ($positions) {
                return count($positions) === 3;
            });
    }

    public function test_can_edit_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('edit', $position->id)
            ->assertSet('editing.id', $position->id)
            ->assertSet('positionForm.name', $position->name);
    }

    public function test_can_reset_form()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('edit', $position->id)
            ->assertSet('editing.id', $position->id)
            ->call('resetForm')
            ->assertSet('editing', null)
            ->assertSet('positionForm.name', '');
    }

    public function test_can_save_new_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'New Position')
            ->set('positionForm.ticker', null)
            ->set('positionForm.unit', 'USD')
            ->set('positionForm.quantity', 10)
            ->set('positionForm.price', 100)
            ->call('save');

        $this->assertDatabaseHas('wallet_positions', [
            'wallet_id' => $wallet->id,
            'name' => 'New Position',
            'quantity' => '10',
            'price' => '100',
        ]);
    }

    public function test_can_update_existing_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('edit', $position->id)
            ->set('positionForm.name', 'Updated Position')
            ->call('save');

        $this->assertDatabaseHas('wallet_positions', [
            'id' => $position->id,
            'name' => 'Updated Position',
        ]);
    }

    public function test_can_delete_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('delete', $position->id);

        $this->assertDatabaseMissing('wallet_positions', ['id' => $position->id]);
    }

    public function test_validation_for_position_form()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->set('positionForm.name', '')
            ->set('positionForm.quantity', -1)
            ->call('save')
            ->assertHasErrors(['positionForm.name', 'positionForm.quantity', 'positionForm.price']);
    }

    public function test_get_currency_symbol()
    {
        $user = User::factory()->create();
        $user->preference()->create(['currency' => 'USD']);
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->assertSet('userCurrency', 'USD');
    }

    public function test_handles_edit_nonexistent_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('edit', '00000000-0000-0000-0000-000000000000')
            ->assertStatus(200);
    }

    public function test_handles_delete_nonexistent_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('delete', '00000000-0000-0000-0000-000000000000')
            ->assertStatus(200);
    }

    public function test_can_update_prices()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        WalletPosition::factory()->for($wallet)->create(['ticker' => 'BTC']);
        WalletPosition::factory()->for($wallet)->create(['ticker' => 'ETH']);
        WalletPosition::factory()->for($wallet)->create(['ticker' => null]); // No ticker
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('updatePrices')
            ->assertStatus(200);
    }

    public function test_handles_save_exception()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        // Mock the wallet to throw an exception when creating positions
        $this->mock(Wallet::class, function ($mock) use ($wallet) {
            $mock->shouldReceive('positions->create')->andThrow(new \Exception('Database error'));
        });

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Test Position')
            ->set('positionForm.quantity', 1)
            ->set('positionForm.price', 100)
            ->call('save')
            ->assertStatus(200);
    }

    public function test_handles_delete_exception()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create();
        $this->actingAs($user);

        // Mock the position to throw an exception when deleting
        $this->mock(WalletPosition::class, function ($mock) {
            $mock->shouldReceive('delete')->andThrow(new \Exception('Database error'));
        });

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('delete', $position->id)
            ->assertStatus(200);
    }

    public function test_can_refresh_list()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        WalletPosition::factory()->for($wallet)->count(2)->create();
        $this->actingAs($user);

        $component = Livewire::test(WalletPositions::class, ['wallet' => $wallet]);

        // Add another position
        WalletPosition::factory()->for($wallet)->create();

        // Refresh the list
        $component->call('refreshList')
            ->assertViewHas('positions', function ($positions) {
                return count($positions) === 3;
            });
    }

    public function test_handles_position_with_null_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => null]);
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('edit', $position->id)
            ->assertSet('positionForm.ticker', '');
    }

    public function test_handles_position_with_empty_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => '']);
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('edit', $position->id)
            ->assertSet('positionForm.ticker', '');
    }
}
