<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class InitializeWalletOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:initialize-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize order field for existing wallets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Initializing wallet order...');

        $users = User::all();
        $totalUpdated = 0;

        foreach ($users as $user) {
            $wallets = $user->wallets()->orderBy('created_at', 'asc')->get();

            foreach ($wallets as $index => $wallet) {
                $wallet->update(['order' => $index + 1]);
                $totalUpdated++;
            }
        }

        $this->info("Successfully updated {$totalUpdated} wallets with order values.");

        return Command::SUCCESS;
    }
}
