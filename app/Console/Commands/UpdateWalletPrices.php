<?php

namespace App\Console\Commands;

use App\Jobs\UpdateWalletPricesJob;
use App\Models\WalletPosition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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
            $this->info('Dispatching wallet price update job to background...');
            UpdateWalletPricesJob::dispatch();
            $this->info('✅ Price update job dispatched successfully!');

            return 0;
        }

        if ($this->option('clear-cache')) {
            $this->info('Clearing price cache...');
            $this->clearPriceCache();
        }

        $this->info('Updating wallet position prices...');

        $positions = WalletPosition::whereNotNull('ticker')->get();

        if ($positions->isEmpty()) {
            $this->info('No positions with tickers found.');

            return 0;
        }

        $updated = 0;
        $failed = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($positions->count());
        $progressBar->start();

        foreach ($positions as $position) {
            try {
                // Skip if recently updated (unless force flag is used)
                if (! $this->option('force') && $this->wasRecentlyUpdated($position)) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                if ($position->updateCurrentPrice()) {
                    $updated++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->warn("Failed to update price for {$position->name} ({$position->ticker}): ".$e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("✅ Updated: {$updated} positions");
        if ($skipped > 0) {
            $this->comment("⏭️ Skipped: {$skipped} positions (recently updated)");
        }
        if ($failed > 0) {
            $this->warn("❌ Failed: {$failed} positions");
        }

        $this->info('Price update completed!');

        return 0;
    }

    /**
     * Check if position was recently updated (within last 10 minutes)
     */
    private function wasRecentlyUpdated(WalletPosition $position): bool
    {
        return $position->updated_at->isAfter(now()->subMinutes(10));
    }

    /**
     * Clear all price-related cache entries
     */
    private function clearPriceCache(): void
    {
        try {
            // Only works with Redis cache driver
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys('*price_*');
                if (! empty($keys)) {
                    Cache::getRedis()->del($keys);
                    $this->info('Cleared '.count($keys).' price cache entries.');
                }
            } else {
                // For other cache drivers, clear all cache
                Cache::flush();
                $this->info('Cleared all cache entries (non-Redis driver).');
            }
        } catch (\Exception $e) {
            $this->warn('Could not clear price cache: '.$e->getMessage());
        }
    }
}
