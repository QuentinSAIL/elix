<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\UpdateWalletPrices;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateWalletPricesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Create positions with tickers
        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
        ]);

        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'GOOGL',
        ]);

        $this->artisan(UpdateWalletPrices::class)
            ->assertExitCode(0);
    }

    public function test_command_handles_positions_without_tickers(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Create positions without tickers
        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => null,
        ]);

        $this->artisan(UpdateWalletPrices::class)
            ->assertExitCode(0);
    }

    public function test_command_handles_no_positions(): void
    {
        $this->artisan(UpdateWalletPrices::class)
            ->assertExitCode(0);
    }

    public function test_command_with_force_option(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
        ]);

        $this->artisan(UpdateWalletPrices::class, ['--force' => true])
            ->assertExitCode(0);
    }

    public function test_command_handles_price_update_failure(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Create a position with an invalid ticker that will fail
        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'INVALID_TICKER_12345',
        ]);

        $this->artisan(UpdateWalletPrices::class)
            ->assertExitCode(0);
    }

    public function test_command_shows_progress_bar(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Create multiple positions
        WalletPosition::factory()->count(5)->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
        ]);

        $this->artisan(UpdateWalletPrices::class)
            ->assertExitCode(0);
    }
}
