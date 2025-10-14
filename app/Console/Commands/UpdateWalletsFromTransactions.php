<?php

namespace App\Console\Commands;

use App\Services\WalletUpdateService;
use Illuminate\Console\Command;

class UpdateWalletsFromTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:update-from-transactions {--recalculate : Recalculate all wallet balances from scratch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update wallet balances based on categorized transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $walletUpdateService = app(WalletUpdateService::class);

        if ($this->option('recalculate')) {
            $this->info('Recalculating all wallet balances from transactions...');

            // Get all single mode wallets
            $wallets = \App\Models\Wallet::where('mode', 'single')->get();

            foreach ($wallets as $wallet) {
                $walletUpdateService->recalculateWalletBalance($wallet);
                $this->line("Recalculated wallet: {$wallet->name}");
            }

            $this->info("Recalculated {$wallets->count()} wallets.");
        } else {
            $this->info('Processing transactions for wallet updates...');

            $processedCount = $walletUpdateService->processAllUncategorizedTransactions();

            $this->info("Processed {$processedCount} transactions for wallet updates.");
        }

        return Command::SUCCESS;
    }
}
