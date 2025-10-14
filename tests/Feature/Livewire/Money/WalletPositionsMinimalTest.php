<?php

namespace Tests\Feature\Livewire\Money;

use App\Livewire\Money\WalletPositions;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WalletPositionsMinimalTest extends TestCase
{
    use RefreshDatabase;

    #[test]
    public function it_renders_successfully()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(WalletPositions::class, ['wallet' => $wallet])
            ->assertStatus(200);
    }
}
