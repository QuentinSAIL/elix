<?php

namespace App\Console\Commands;

use App\Services\PriceService;
use Illuminate\Console\Command;

class TestPriceService extends Command
{
    protected $signature = 'test:price-service {ticker?}';
    protected $description = 'Test the price service with various tickers';

    public function handle(): int
    {
        $priceService = app(PriceService::class);

        $ticker = $this->argument('ticker') ?? 'AAPL';

        $this->info("Testing price service for ticker: {$ticker}");
        $this->newLine();

        // Test single price
        $price = $priceService->getPrice($ticker, 'USD');

        if ($price !== null) {
            $this->info("✅ Price found: {$price} USD");
        } else {
            $this->error("❌ No price found for {$ticker}");
        }

        $this->newLine();

        // Test multiple tickers
        $tickers = ['AAPL', 'GOOGL', 'MSFT', 'BTC', 'ETH'];
        $this->info("Testing multiple tickers:");

        foreach ($tickers as $testTicker) {
            $testPrice = $priceService->getPrice($testTicker, 'USD');
            if ($testPrice !== null) {
                $this->line("  ✅ {$testTicker}: {$testPrice} USD");
            } else {
                $this->line("  ❌ {$testTicker}: No price found");
            }
        }

        $this->newLine();
        $this->info("Price service test completed!");

        return 0;
    }
}
