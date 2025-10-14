<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\UpdateWalletPrices;
use App\Jobs\UpdateWalletPricesJob;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
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

        $this->artisan(UpdateWalletPrices::class)->assertExitCode(0);
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

        $this->artisan(UpdateWalletPrices::class)->assertExitCode(0);
    }

    public function test_command_handles_no_positions(): void
    {
        $this->artisan(UpdateWalletPrices::class)->assertExitCode(0);
    }

    public function test_command_with_force_option(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
        ]);

        $this->artisan(UpdateWalletPrices::class, ['--force' => true])->assertExitCode(0);
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

        $this->artisan(UpdateWalletPrices::class)->assertExitCode(0);
    }

    public function test_command_shows_progress_bar(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Create multiple positions
        WalletPosition::factory()
            ->count(5)
            ->create([
                'wallet_id' => $wallet->id,
                'ticker' => 'AAPL',
            ]);

        $this->artisan(UpdateWalletPrices::class)->assertExitCode(0);
    }

    public function test_dispatches_background_job_when_option_passed(): void
    {
        Bus::fake();

        $this->artisan(UpdateWalletPrices::class, ['--background' => true])
            ->expectsOutput('Dispatching wallet price update job to background...')
            ->expectsOutput('✅ Price update job dispatched successfully!')
            ->assertExitCode(0);

        Bus::assertDispatched(UpdateWalletPricesJob::class);
    }

    public function test_clear_cache_non_redis_driver_flushes_cache(): void
    {
        // Put some cache entries that should be flushed by the command
        Cache::put('price_ABC', 'x');
        Cache::put('other_key', 'y');

        $this->assertTrue(Cache::has('price_ABC'));
        $this->assertTrue(Cache::has('other_key'));

        $this->artisan(UpdateWalletPrices::class, ['--clear-cache' => true])
            ->expectsOutput('Clearing price cache...')
            ->expectsOutput('Cleared all cache entries (non-Redis driver).')
            ->assertExitCode(0);

        $this->assertFalse(Cache::has('price_ABC'));
        $this->assertFalse(Cache::has('other_key'));
    }

    public function test_skips_recently_updated_positions_and_updates_older_ones(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Recently updated position should be skipped
        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
            'updated_at' => Carbon::now(),
        ]);

        // Older position should be updated
        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'MSFT',
            'updated_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->artisan(UpdateWalletPrices::class)
            ->expectsOutput('Updating wallet position prices...')
            ->expectsOutput('✅ Updated: 1 positions')
            ->expectsOutput('⏭️ Skipped: 1 positions (recently updated)');
    }

    public function test_force_option_updates_even_recent_positions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Recently updated position that would normally be skipped
        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
            'updated_at' => Carbon::now(),
        ]);

        $this->artisan(UpdateWalletPrices::class, ['--force' => true])
            ->expectsOutput('Updating wallet position prices...')
            ->expectsOutput('✅ Updated: 1 positions')
            ->assertExitCode(0);
    }
}
