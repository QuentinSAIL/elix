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

        // Group positions by ticker to avoid duplicate API calls
        $positionsByTicker = [];
        $positions = WalletPosition::whereNotNull('ticker')->get();

        foreach ($positions as $position) {
            $ticker = strtoupper($position->ticker);
            $positionsByTicker[$ticker][] = $position;
        }

        $updated = 0;
        $failed = 0;

        // Update prices for each ticker group
        foreach ($positionsByTicker as $ticker => $tickerPositions) {
            try {
                // Use the first position to get the current price
                $firstPosition = $tickerPositions[0];
                $priceService = app(\App\Services\PriceService::class);
                $currentPrice = $priceService->getPrice($firstPosition->ticker, $firstPosition->wallet->unit);

                if ($currentPrice !== null) {
                    // Update all positions with the same ticker
                    foreach ($tickerPositions as $position) {
                        $position->update(['price' => (string) $currentPrice]);
                        $updated++;
                    }
                } else {
                    $failed += count($tickerPositions);
                    Log::warning(__('Failed to get price for ticker :ticker', ['ticker' => $ticker]));
                }
            } catch (\Exception $e) {
                $failed += count($tickerPositions);
                Log::warning(__('Failed to update price for ticker :ticker: :error', ['ticker' => $ticker, 'error' => $e->getMessage()]));
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
