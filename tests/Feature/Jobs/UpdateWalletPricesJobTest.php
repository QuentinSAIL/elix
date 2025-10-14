<?php

namespace Tests\Feature\Jobs;

use App\Jobs\UpdateWalletPricesJob;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateWalletPricesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_dispatched(): void
    {
        Queue::fake();

        UpdateWalletPricesJob::dispatch();

        Queue::assertPushed(UpdateWalletPricesJob::class);
    }

    public function test_job_updates_wallet_prices(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        // Create positions with tickers
        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => 100.0,
        ]);
        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'BTC',
            'price' => 50000.0,
        ]);

        // Mock HTTP responses for price APIs
        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
            'api.coingecko.com/*' => \Illuminate\Support\Facades\Http::response([
                'bitcoin' => ['eur' => 45000],
            ]),
        ]);

        $job = new UpdateWalletPricesJob;
        $job->handle();

        // Verify prices were updated
        $this->assertDatabaseHas('wallet_positions', [
            'ticker' => 'AAPL',
            'price' => '150.25',
        ]);
    }

    public function test_job_handles_failures_gracefully(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'INVALID',
            'price' => 100.0,
        ]);

        // Mock API failure
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([], 500),
        ]);

        $job = new UpdateWalletPricesJob;
        $job->handle();

        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }
}
