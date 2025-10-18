<?php

namespace App\Console\Commands;

use App\Jobs\UpdateWalletPricesJob;
use App\Models\WalletPosition;
use App\Models\PriceAsset;
use App\Services\PriceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class UpdateWalletPrices extends Command
{
    protected $signature = 'wallets:update-prices
                            {--force : Force update even if price was recently updated}
                            {--background : Run update in background job}
                            {--clear-cache : Clear price cache before updating}';

    protected $description = 'Update current market prices for all wallet positions with tickers';

    public function handle(): int
    {
        if ($this->option('background')) {
            $this->info(__('Dispatching wallet price update job to background'));
            UpdateWalletPricesJob::dispatch();
            $this->info('✅ Price update job dispatched successfully!');

            return 0;
        }

        if ($this->option('clear-cache')) {
            $this->info(__('Clearing price cache'));
            $this->clearPriceCache();
        }

        $this->info(__('Updating wallet position prices using price_assets system'));

        // Get unique tickers from positions
        $tickers = WalletPosition::whereNotNull('ticker')
            ->distinct()
            ->pluck('ticker')
            ->toArray();

        if (empty($tickers)) {
            $this->info(__('No positions with tickers found'));
            return 0;
        }

        $this->info(__('Found :count unique tickers to update', ['count' => count($tickers)]));

        $updated = 0;
        $failed = 0;
        $skipped = 0;

        $priceService = app(PriceService::class);
        $progressBar = $this->output->createProgressBar(count($tickers));
        $progressBar->start();

        foreach ($tickers as $ticker) {
            try {
                // Check if we should skip this ticker
                $priceAsset = PriceAsset::where('ticker', $ticker)->first();

                if (!$this->option('force') && $priceAsset && $priceAsset->isPriceRecent()) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Determine unit type from positions
                $position = WalletPosition::where('ticker', $ticker)->first();
                $unitType = $this->getUnitTypeFromPosition($position);

                // Update price using PriceService (which updates price_assets)
                $price = $priceService->forceUpdatePrice($ticker, 'EUR', $unitType);

                if ($price !== null) {
                    $updated++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->warn(__('Failed to update price for :ticker: :error', ['ticker' => $ticker, 'error' => $e->getMessage()]));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Synchronize all positions with updated price_assets
        $this->info(__('Synchronizing position prices...'));
        $syncedCount = $this->synchronizePositionPrices();

        $this->info("✅ Updated: {$updated} price assets");
        $this->info("🔄 Synchronized: {$syncedCount} positions");
        if ($skipped > 0) {
            $this->comment("⏭️ Skipped: {$skipped} tickers (recently updated)");
        }
        if ($failed > 0) {
            $this->warn("❌ Failed: {$failed} tickers");
        }

        $this->info(__('Price update completed'));

        return 0;
    }

    /**
     * Get unit type from position for API calls
     */
    private function getUnitTypeFromPosition(WalletPosition $position): ?string
    {
        return match ($position->unit) {
            'CRYPTO', 'TOKEN' => 'CRYPTO',
            'STOCK' => 'STOCK',
            'COMMODITY' => 'COMMODITY',
            'ETF' => 'ETF',
            'BOND' => 'BOND',
            default => 'OTHER',
        };
    }

    /**
     * Synchronize position prices with price_assets table
     */
    private function synchronizePositionPrices(): int
    {
        $syncedCount = 0;

        WalletPosition::whereNotNull('ticker')->get()->each(function ($position) use (&$syncedCount) {
            $priceAsset = PriceAsset::where('ticker', $position->ticker)->first();

            if ($priceAsset && $priceAsset->price) {
                $position->update(['price' => (string) $priceAsset->price]);
                $syncedCount++;
            }
        });

        return $syncedCount;
    }

    /**
     * Clear all price-related cache entries
     */
    private function clearPriceCache(): void
    {
        try {
            // Only works with Redis cache driver
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Redis::keys('*price_*');
                if (! empty($keys)) {
                    Redis::del($keys);
                    $this->info(__('Cleared :count price cache entries', ['count' => count($keys)]));
                }
            } else {
                // For other cache drivers, clear all cache
                Cache::flush();
                $this->info(__('Cleared all cache entries (non-Redis driver)'));
            }
        } catch (\Exception $e) {
            $this->warn(__('Could not clear price cache: :error', ['error' => $e->getMessage()]));
        }
    }
}
