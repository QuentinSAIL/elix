<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateWalletPricesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_prices_successfully(): void
    {
        // Clear cache before test
        \Illuminate\Support\Facades\Cache::flush();

        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => 100.0,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
            'query1.finance.yahoo.com/*' => \Illuminate\Support\Facades\Http::response([], 500),
            'api.coingecko.com/*' => \Illuminate\Support\Facades\Http::response([], 500),
        ]);

        $this->artisan('wallets:update-prices --force')
            ->assertExitCode(0);

        $this->assertDatabaseHas('wallet_positions', [
            'ticker' => 'AAPL',
            'price' => '150.25',
        ]);
    }

    public function test_command_skips_recently_updated_positions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => 100.0,
        ]);

        // Create a recent PriceAsset to simulate recently updated price
        \App\Models\PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 100.0,
            'currency' => 'EUR',
            'last_updated' => now(),
        ]);

        $this->artisan('wallets:update-prices')
            ->expectsOutput('⏭️ Skipped: 1 tickers (recently updated)')
            ->assertExitCode(0);
    }

    public function test_command_force_updates_recently_updated_positions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => 100.0,
            'updated_at' => now(), // Recently updated
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
        ]);

        $this->artisan('wallets:update-prices --force')
            ->expectsOutput('✅ Updated: 1 price assets')
            ->assertExitCode(0);
    }

    public function test_command_handles_no_positions(): void
    {
        $this->artisan('wallets:update-prices')
            ->expectsOutput(__('No positions with tickers found'))
            ->assertExitCode(0);
    }
}
