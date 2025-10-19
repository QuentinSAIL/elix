<?php

namespace Tests\Feature\Livewire\Money;

use App\Livewire\Money\WalletForm;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WalletFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_form_can_be_rendered()
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(WalletForm::class)
            ->assertStatus(200);
    }

    public function test_can_create_single_mode_wallet()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(WalletForm::class)
            ->set('walletForm.name', 'Test Wallet')
            ->set('walletForm.unit', 'USD')
            ->set('walletForm.mode', 'single')
            ->set('walletForm.balance', 1000)
            ->call('save');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'name' => 'Test Wallet',
            'unit' => 'USD',
            'mode' => 'single',
            'balance' => '1000',
        ]);
    }

    public function test_can_create_multi_mode_wallet()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(WalletForm::class)
            ->set('walletForm.name', 'Crypto Wallet')
            ->set('walletForm.unit', 'EUR')
            ->set('walletForm.mode', 'multi')
            ->call('save');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'name' => 'Crypto Wallet',
            'unit' => 'EUR',
            'mode' => 'multi',
            'balance' => '0',
        ]);
    }

    public function test_can_update_wallet()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->set('walletForm.name', 'Updated Wallet Name')
            ->call('save');

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'name' => 'Updated Wallet Name',
        ]);
    }

    public function test_validation_for_wallet_form()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(WalletForm::class)
            ->set('walletForm.name', '')
            ->set('walletForm.unit', '')
            ->set('walletForm.mode', '')
            ->call('save')
            ->assertHasErrors(['walletForm.name', 'walletForm.unit', 'walletForm.mode']);
    }

    public function test_can_add_position_to_wallet()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Bitcoin')
            ->set('positionForm.ticker', null)
            ->set('positionForm.quantity', 1.5)
            ->set('positionForm.price', 50000)
            ->call('savePosition');

        $this->assertDatabaseHas('wallet_positions', [
            'wallet_id' => $wallet->id,
            'name' => 'Bitcoin',
            'ticker' => null,
            'quantity' => '1.5',
            'price' => '50000',
        ]);
    }

    public function test_can_update_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $position = $wallet->positions()->create(['name' => 'old', 'quantity' => 1, 'price' => 1]);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->call('editPosition', $position->id)
            ->set('positionForm.name', 'Ethereum')
            ->call('savePosition');

        $this->assertDatabaseHas('wallet_positions', [
            'id' => $position->id,
            'name' => 'Ethereum',
        ]);
    }

    public function test_can_delete_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $position = $wallet->positions()->create(['name' => 'old', 'quantity' => 1, 'price' => 1]);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->call('deletePosition', $position->id);

        $this->assertDatabaseMissing('wallet_positions', [
            'id' => $position->id,
        ]);
    }

    public function test_can_cancel_edit_position()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $position = $wallet->positions()->create(['name' => 'old', 'quantity' => 1, 'price' => 1]);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->call('editPosition', $position->id)
            ->assertSet('editingPosition.id', $position->id)
            ->call('cancelEditPosition')
            ->assertSet('editingPosition', null);
    }

    public function test_can_populate_form_for_existing_wallet()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create([
            'name' => 'Test Wallet',
            'unit' => 'USD',
            'mode' => 'single',
            'balance' => 1000,
        ]);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->call('populateForm')
            ->assertSet('walletForm.name', 'Test Wallet')
            ->assertSet('walletForm.unit', 'USD')
            ->assertSet('walletForm.mode', 'single')
            ->assertSet('walletForm.balance', '1000');
    }

    public function test_can_edit_position_with_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $position = $wallet->positions()->create([
            'name' => 'Bitcoin',
            'ticker' => 'BTC',
            'quantity' => 1,
            'price' => 50000,
        ]);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->call('editPosition', $position->id)
            ->assertSet('editingPosition.id', $position->id)
            ->assertSet('positionForm.name', 'Bitcoin')
            ->assertSet('positionForm.ticker', 'BTC')
            ->assertSet('positionForm.quantity', '1.000000000000000000')
            ->assertSet('positionForm.price', '50000.000000000000000000');
    }

    public function test_can_edit_position_without_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $position = $wallet->positions()->create([
            'name' => 'Gold',
            'ticker' => null,
            'quantity' => 10,
            'price' => 2000,
        ]);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->call('editPosition', $position->id)
            ->assertSet('editingPosition.id', $position->id)
            ->assertSet('positionForm.name', 'Gold')
            ->assertSet('positionForm.ticker', '')
            ->assertSet('positionForm.quantity', '10.000000000000000000')
            ->assertSet('positionForm.price', '2000.000000000000000000');
    }

    public function test_can_add_position_with_ticker()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Ethereum')
            ->set('positionForm.ticker', 'ETH')
            ->set('positionForm.quantity', 2.5)
            ->set('positionForm.price', 3000)
            ->call('savePosition');

        $this->assertDatabaseHas('wallet_positions', [
            'wallet_id' => $wallet->id,
            'name' => 'Ethereum',
            'ticker' => 'ETH',
            'quantity' => '2.500000000000000000',
        ]);
    }

    public function test_position_validation()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->set('positionForm.name', '')
            ->set('positionForm.quantity', '')
            ->set('positionForm.price', '')
            ->call('savePosition')
            ->assertHasErrors([
                'positionForm.name' => 'required',
                'positionForm.quantity' => 'required',
                'positionForm.price' => 'required',
            ]);
    }

    public function test_position_validation_numeric_fields()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $this->actingAs($user);

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Test')
            ->set('positionForm.quantity', 'invalid')
            ->set('positionForm.price', 'invalid')
            ->call('savePosition')
            ->assertHasErrors([
                'positionForm.quantity' => 'numeric',
                'positionForm.price' => 'numeric',
            ]);
    }

    public function test_can_handle_save_position_exception()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $this->actingAs($user);

        // Mock the position creation to throw an exception
        $this->mock(\App\Models\WalletPosition::class, function ($mock) {
            $mock->shouldReceive('create')
                ->andThrow(new \Exception('Database error'));
        });

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->set('positionForm.name', 'Bitcoin')
            ->set('positionForm.quantity', 1)
            ->set('positionForm.price', 50000)
            ->call('savePosition')
            ->assertHasNoErrors(); // Should handle exception gracefully
    }

    public function test_can_handle_delete_position_exception()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['mode' => 'multi']);
        $position = $wallet->positions()->create(['name' => 'old', 'quantity' => 1, 'price' => 1]);
        $this->actingAs($user);

        // Mock the position deletion to throw an exception
        $this->mock(\App\Models\WalletPosition::class, function ($mock) {
            $mock->shouldReceive('find')
                ->andReturn($mock);
            $mock->shouldReceive('delete')
                ->andThrow(new \Exception('Database error'));
        });

        Livewire::test(WalletForm::class, ['wallet' => $wallet])
            ->call('deletePosition', $position->id)
            ->assertHasNoErrors(); // Should handle exception gracefully
    }
}
