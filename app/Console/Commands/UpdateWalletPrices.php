<?php

namespace App\Console\Commands;

use App\Models\WalletPosition;
use Illuminate\Console\Command;

class UpdateWalletPrices extends Command
{
    protected $signature = 'wallets:update-prices {--force : Force update even if price was recently updated}';
    protected $description = 'Update current market prices for all wallet positions with tickers';

    public function handle(): int
    {
        $this->info('Updating wallet position prices...');

        $positions = WalletPosition::whereNotNull('ticker')->get();
        $updated = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($positions->count());
        $progressBar->start();

        foreach ($positions as $position) {
            try {
                if ($position->updateCurrentPrice()) {
                    $updated++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->warn("Failed to update price for {$position->name} ({$position->ticker}): " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("✅ Updated: {$updated} positions");
        if ($failed > 0) {
            $this->warn("❌ Failed: {$failed} positions");
        }

        $this->info('Price update completed!');

        return 0;
    }
}
