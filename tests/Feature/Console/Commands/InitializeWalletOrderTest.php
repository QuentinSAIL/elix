<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\InitializeWalletOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitializeWalletOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully(): void
    {
        $this->artisan(InitializeWalletOrder::class)
            ->assertExitCode(0);
    }

    public function test_command_initializes_wallet_order(): void
    {
        $user = User::factory()->create();
        
        // Create wallets without order
        $wallet1 = Wallet::factory()->create(['user_id' => $user->id, 'order' => null]);
        $wallet2 = Wallet::factory()->create(['user_id' => $user->id, 'order' => null]);
        $wallet3 = Wallet::factory()->create(['user_id' => $user->id, 'order' => null]);

        $this->artisan(InitializeWalletOrder::class)
            ->assertExitCode(0);

        $wallet1->refresh();
        $wallet2->refresh();
        $wallet3->refresh();

        $this->assertNotNull($wallet1->order);
        $this->assertNotNull($wallet2->order);
        $this->assertNotNull($wallet3->order);
    }

    public function test_command_handles_no_wallets(): void
    {
        $this->artisan(InitializeWalletOrder::class)
            ->assertExitCode(0);
    }
}

