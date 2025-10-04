<?php

use App\Console\Commands\TestPriceService;
use App\Services\PriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('test price service command runs successfully', function () {
    $mockPriceService = Mockery::mock(PriceService::class);
    $mockPriceService->shouldReceive('getPrice')
        ->with('AAPL', 'USD')
        ->andReturn(150.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('GOOGL', 'USD')
        ->andReturn(2500.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('MSFT', 'USD')
        ->andReturn(300.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('BTC', 'USD')
        ->andReturn(45000.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('ETH', 'USD')
        ->andReturn(3000.00);

    $this->app->instance(PriceService::class, $mockPriceService);

    $this->artisan(TestPriceService::class)
        ->expectsOutput('Testing price service for ticker: AAPL')
        ->expectsOutput('✅ Price found: 150 USD')
        ->expectsOutput('Testing multiple tickers:')
        ->expectsOutput('  ✅ AAPL: 150 USD')
        ->expectsOutput('  ✅ GOOGL: 2500 USD')
        ->expectsOutput('  ✅ MSFT: 300 USD')
        ->expectsOutput('  ✅ BTC: 45000 USD')
        ->expectsOutput('  ✅ ETH: 3000 USD')
        ->expectsOutput('Price service test completed!')
        ->assertExitCode(0);
});

test('test price service command with custom ticker', function () {
    $mockPriceService = Mockery::mock(PriceService::class);
    $mockPriceService->shouldReceive('getPrice')
        ->with('TSLA', 'USD')
        ->andReturn(200.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('AAPL', 'USD')
        ->andReturn(150.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('GOOGL', 'USD')
        ->andReturn(2500.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('MSFT', 'USD')
        ->andReturn(300.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('BTC', 'USD')
        ->andReturn(45000.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('ETH', 'USD')
        ->andReturn(3000.00);

    $this->app->instance(PriceService::class, $mockPriceService);

    $this->artisan(TestPriceService::class, ['ticker' => 'TSLA'])
        ->expectsOutput('Testing price service for ticker: TSLA')
        ->expectsOutput('✅ Price found: 200 USD')
        ->assertExitCode(0);
});

test('test price service command handles null prices', function () {
    $mockPriceService = Mockery::mock(PriceService::class);
    $mockPriceService->shouldReceive('getPrice')
        ->with('INVALID', 'USD')
        ->andReturn(null);
    $mockPriceService->shouldReceive('getPrice')
        ->with('AAPL', 'USD')
        ->andReturn(150.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('GOOGL', 'USD')
        ->andReturn(null);
    $mockPriceService->shouldReceive('getPrice')
        ->with('MSFT', 'USD')
        ->andReturn(300.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('BTC', 'USD')
        ->andReturn(45000.00);
    $mockPriceService->shouldReceive('getPrice')
        ->with('ETH', 'USD')
        ->andReturn(3000.00);

    $this->app->instance(PriceService::class, $mockPriceService);

    $this->artisan(TestPriceService::class, ['ticker' => 'INVALID'])
        ->expectsOutput('Testing price service for ticker: INVALID')
        ->expectsOutput('❌ No price found for INVALID')
        ->expectsOutput('Testing multiple tickers:')
        ->expectsOutput('  ✅ AAPL: 150 USD')
        ->expectsOutput('  ❌ GOOGL: No price found')
        ->expectsOutput('  ✅ MSFT: 300 USD')
        ->expectsOutput('  ✅ BTC: 45000 USD')
        ->expectsOutput('  ✅ ETH: 3000 USD')
        ->assertExitCode(0);
});

