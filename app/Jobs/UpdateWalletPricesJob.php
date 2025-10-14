<?php

namespace App\Jobs;

use App\Models\WalletPosition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateWalletPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info(__('Starting background wallet price update job'));

        $positions = WalletPosition::whereNotNull('ticker')->get();
        $updated = 0;
        $failed = 0;

        foreach ($positions as $position) {
            try {
                if ($position->updateCurrentPrice()) {
                    $updated++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::warning(__('Failed to update price for :name (:ticker): :error', ['name' => $position->name, 'ticker' => $position->ticker, 'error' => $e->getMessage()]));
            }
        }

        Log::info(__('Price update job completed: :updated updated, :failed failed', ['updated' => $updated, 'failed' => $failed]));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(__('Wallet price update job failed: :error', ['error' => $exception->getMessage()]));
    }
}
