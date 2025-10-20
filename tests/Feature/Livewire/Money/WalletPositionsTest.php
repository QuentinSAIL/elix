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
        $this->mock(Wallet::class, function ($mock) {
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

    public function test_can_save_position_with_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
        ]);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Apple Stock')
            ->set('positionForm.ticker', 'AAPL')
            ->set('positionForm.unit', 'STOCK')
            ->set('positionForm.quantity', 10)
            ->set('positionForm.price', 100)
            ->call('save');

        $this->assertDatabaseHas('wallet_positions', [
            'wallet_id' => $wallet->id,
            'name' => 'Apple Stock',
            'ticker' => 'AAPL',
        ]);
    }

    public function test_can_update_position_price()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();

        // Create a price asset
        \App\Models\PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'price' => '150.25',
            'last_updated' => now(),
        ]);

        $position = WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => '100.00',
        ]);
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('updatePositionPrice', $position->id)
            ->assertStatus(200);
    }

    public function test_handles_update_position_price_without_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => null]);
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('updatePositionPrice', $position->id)
            ->assertStatus(200);
    }

    public function test_handles_update_position_price_nonexistent()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('updatePositionPrice', '00000000-0000-0000-0000-000000000000')
            ->assertStatus(200);
    }

    public function test_handles_update_position_price_api_failure()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'INVALID',
            'price' => '100.00',
        ]);
        $this->actingAs($user);

        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([], 500),
            'query1.finance.yahoo.com/*' => \Illuminate\Support\Facades\Http::response([], 500),
        ]);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('updatePositionPrice', $position->id)
            ->assertStatus(200);
    }

    public function test_can_save_position_with_recent_price_asset()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        // Create a recent price asset
        \App\Models\PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'price' => '150.25',
            'last_updated' => now(),
        ]);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Apple Stock')
            ->set('positionForm.ticker', 'AAPL')
            ->set('positionForm.unit', 'STOCK')
            ->set('positionForm.quantity', 10)
            ->set('positionForm.price', 100)
            ->call('save');

        $this->assertDatabaseHas('wallet_positions', [
            'wallet_id' => $wallet->id,
            'name' => 'Apple Stock',
            'ticker' => 'AAPL',
        ]);
    }

    public function test_can_save_position_with_old_price_asset()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        // Create an old price asset
        \App\Models\PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'price' => '100.00',
            'last_updated' => now()->subHours(13),
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
        ]);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Apple Stock')
            ->set('positionForm.ticker', 'AAPL')
            ->set('positionForm.unit', 'STOCK')
            ->set('positionForm.quantity', 10)
            ->set('positionForm.price', 100)
            ->call('save');

        $this->assertDatabaseHas('wallet_positions', [
            'wallet_id' => $wallet->id,
            'name' => 'Apple Stock',
            'ticker' => 'AAPL',
        ]);
    }

    public function test_handles_ticker_price_update_exception()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        // Mock PriceService to throw exception
        $this->mock(\App\Services\PriceService::class, function ($mock) {
            $mock->shouldReceive('getPrice')->andThrow(new \Exception('API error'));
            $mock->shouldReceive('calculatePositionsValueInCurrency')->andReturn(0);
        });

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Test Position')
            ->set('positionForm.ticker', 'TEST')
            ->set('positionForm.unit', 'STOCK')
            ->set('positionForm.quantity', 10)
            ->set('positionForm.price', 100)
            ->call('save')
            ->assertStatus(200);
    }

    public function test_update_prices_with_multiple_positions_same_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();

        // Create price asset
        \App\Models\PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'price' => '150.25',
            'last_updated' => now(),
        ]);

        // Create multiple positions with same ticker
        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => '100.00',
        ]);
        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => '100.00',
        ]);
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('updatePrices')
            ->assertStatus(200);
    }

    public function test_update_position_price_with_multiple_positions_same_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();

        // Create price asset
        \App\Models\PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'price' => '150.25',
            'last_updated' => now(),
        ]);

        // Create multiple positions with same ticker
        $position1 = WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => '100.00',
        ]);
        $position2 = WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => '100.00',
        ]);
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('updatePositionPrice', $position1->id)
            ->assertStatus(200);
    }

    public function test_get_current_price_returns_null_for_no_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => null]);
        $this->actingAs($user);

        $component = Livewire::test(WalletPositions::class, ['wallet' => $wallet]);

        // getCurrentPrice is a public method, we can test it through updatePositionPrice
        $component->call('updatePositionPrice', $position->id);

        // Position should still have null ticker
        $this->assertNull($position->fresh()->ticker);
    }

    public function test_handles_update_prices_with_api_failures()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'INVALID1',
            'price' => '100.00',
        ]);
        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'INVALID2',
            'price' => '100.00',
        ]);
        $this->actingAs($user);

        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([], 500),
            'query1.finance.yahoo.com/*' => \Illuminate\Support\Facades\Http::response([], 500),
        ]);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->call('updatePrices')
            ->assertStatus(200);
    }

    public function test_mount_without_user_preference()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->assertSet('userCurrency', 'EUR');
    }

    public function test_save_position_with_ticker_api_failure()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([], 500),
            'query1.finance.yahoo.com/*' => \Illuminate\Support\Facades\Http::response([], 500),
        ]);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Test Position')
            ->set('positionForm.ticker', 'INVALID')
            ->set('positionForm.unit', 'STOCK')
            ->set('positionForm.quantity', 10)
            ->set('positionForm.price', 100)
            ->call('save');

        $this->assertDatabaseHas('wallet_positions', [
            'wallet_id' => $wallet->id,
            'name' => 'Test Position',
            'ticker' => 'INVALID',
        ]);
    }
}
