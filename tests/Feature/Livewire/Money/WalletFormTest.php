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
}
