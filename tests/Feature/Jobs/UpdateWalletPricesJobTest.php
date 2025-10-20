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

    public function test_job_handles_no_positions(): void
    {
        $job = new UpdateWalletPricesJob;
        $job->handle();

        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }

    public function test_job_handles_positions_without_tickers(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => null,
            'price' => 100.0,
        ]);

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => '',
            'price' => 200.0,
        ]);

        $job = new UpdateWalletPricesJob;
        $job->handle();

        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }

    public function test_job_handles_mixed_success_and_failure(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        // Create positions that will succeed and fail
        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'AAPL',
            'price' => 100.0,
        ]);

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'INVALID_TICKER_12345',
            'price' => 200.0,
        ]);

        // Mock partial API success
        \Illuminate\Support\Facades\Http::fake([
            'alphavantage.co/*' => \Illuminate\Support\Facades\Http::response([
                'Global Quote' => [
                    '05. price' => '150.25',
                ],
            ]),
            '*' => \Illuminate\Support\Facades\Http::response([], 500),
        ]);

        $job = new UpdateWalletPricesJob;
        $job->handle();

        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }

    public function test_job_handles_all_positions_failing(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        // Create positions that will all fail
        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'INVALID_TICKER_1',
            'price' => 100.0,
        ]);

        WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'INVALID_TICKER_2',
            'price' => 200.0,
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

    public function test_job_handles_exception_during_price_update(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['unit' => 'EUR']);

        $position = WalletPosition::factory()->for($wallet)->create([
            'ticker' => 'EXCEPTION_TICKER',
            'price' => 100.0,
        ]);

        // Mock the updateCurrentPrice method to throw an exception
        $this->mock(\App\Models\WalletPosition::class, function ($mock) {
            $mock->shouldReceive('updateCurrentPrice')->andThrow(new \Exception('Price service error'));
        });

        $job = new UpdateWalletPricesJob;
        $job->handle();

        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }

    public function test_job_handles_failed_method(): void
    {
        $exception = new \Exception('Job failed');

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->with(__('Wallet price update job failed: :error', ['error' => 'Job failed']));

        $job = new UpdateWalletPricesJob;
        $job->failed($exception);
    }

    public function test_job_handles_failed_method_with_different_exception(): void
    {
        $exception = new \RuntimeException('Runtime error');

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->with(__('Wallet price update job failed: :error', ['error' => 'Runtime error']));

        $job = new UpdateWalletPricesJob;
        $job->failed($exception);
    }

    public function test_job_handles_failed_method_with_null_exception(): void
    {
        $exception = new \Exception('');

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->with(__('Wallet price update job failed: :error', ['error' => '']));

        $job = new UpdateWalletPricesJob;
        $job->failed($exception);
    }
}
