<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\UpdatePriceAssets;
use App\Models\PriceAsset;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdatePriceAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully(): void
    {
        $this->artisan(UpdatePriceAssets::class)
            ->assertExitCode(0);
    }

    public function test_command_updates_price_assets(): void
    {
        // Create a price asset first
        PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'currency' => 'USD',
        ]);

        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
        ]);

        $this->artisan(UpdatePriceAssets::class)
            ->assertExitCode(0);

        $this->assertDatabaseHas('price_assets', [
            'ticker' => 'AAPL',
            'type' => 'STOCK',
        ]);
    }

    public function test_command_handles_no_positions(): void
    {
        $this->artisan(UpdatePriceAssets::class)
            ->assertExitCode(0);
    }

    public function test_command_handles_api_failure(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'INVALID',
        ]);

        Http::fake([
            'alphavantage.co/*' => Http::response([], 500),
        ]);

        $this->artisan(UpdatePriceAssets::class)
            ->assertExitCode(0);
    }

    public function test_command_with_limit_option(): void
    {
        // Create multiple price assets
        PriceAsset::factory()->count(3)->create([
            'type' => 'STOCK',
            'currency' => 'USD',
        ]);

        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
        ]);

        $this->artisan(UpdatePriceAssets::class, ['--limit' => 2])
            ->assertExitCode(0);
    }

    public function test_command_with_force_option(): void
    {
        // Create a price asset with recent price
        PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'currency' => 'USD',
            'last_updated' => now(),
        ]);

        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
        ]);

        $this->artisan(UpdatePriceAssets::class, ['--force' => true])
            ->assertExitCode(0);
    }

    public function test_command_synchronizes_position_prices(): void
    {
        // Create an old price asset
        $priceAsset = PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'currency' => 'USD',
            'price' => '100.00',
            'last_updated' => now()->subHours(13), // Old price
        ]);

        // Create wallet position with same ticker
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
            'price' => '100.00', // Same old price
        ]);

        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
        ]);

        $this->artisan(UpdatePriceAssets::class)
            ->assertExitCode(0);

        // Check that position price was synchronized with new price
        $this->assertDatabaseHas('wallet_positions', [
            'ticker' => 'AAPL',
            'price' => '150.250000000000000000', // Full decimal precision
        ]);
    }

    public function test_command_handles_exception_during_update(): void
    {
        PriceAsset::factory()->create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'currency' => 'USD',
        ]);

        // Mock PriceService to throw exception
        $this->mock(\App\Services\PriceService::class, function ($mock) {
            $mock->shouldReceive('forceUpdatePrice')
                ->andThrow(new \Exception('API Error'));
        });

        $this->artisan(UpdatePriceAssets::class)
            ->assertExitCode(0);
    }

    public function test_command_handles_crypto_assets(): void
    {
        PriceAsset::factory()->create([
            'ticker' => 'BTC',
            'type' => 'CRYPTO',
            'currency' => 'USD',
        ]);

        Http::fake([
            'api.coingecko.com/*' => Http::response([
                'bitcoin' => [
                    'usd' => 50000.00,
                ],
            ]),
        ]);

        $this->artisan(UpdatePriceAssets::class)
            ->assertExitCode(0);
    }

    public function test_command_handles_commodity_assets(): void
    {
        PriceAsset::factory()->create([
            'ticker' => 'GOLD',
            'type' => 'COMMODITY',
            'currency' => 'USD',
        ]);

        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '2000.00',
                ],
            ]),
        ]);

        $this->artisan(UpdatePriceAssets::class)
            ->assertExitCode(0);
    }
}
