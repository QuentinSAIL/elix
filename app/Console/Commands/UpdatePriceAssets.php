<?php

namespace App\Console\Commands;

use App\Models\PriceAsset;
use App\Services\PriceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdatePriceAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prices:update-assets {--limit=50 : Maximum number of assets to update} {--force : Force update even if price is recent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update prices for all assets in the price_assets table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info('Starting price update for assets...');

        // Récupérer les actifs à mettre à jour
        $query = PriceAsset::query();

        if (! $force) {
            $query->needsUpdate();
        }

        $assets = $query->limit($limit)->get();

        if ($assets->isEmpty()) {
            $this->info('No assets need updating.');

            return 0;
        }

        $this->info("Found {$assets->count()} assets to update.");

        $updated = 0;
        $failed = 0;
        $skipped = 0;

        $priceService = app(PriceService::class);

        foreach ($assets as $asset) {
            try {
                $this->line("Updating {$asset->ticker} ({$asset->type})...");

                // Déterminer le type d'API à utiliser
                $unitType = $this->getUnitTypeFromAssetType($asset->type);

                // Récupérer le prix depuis l'API et le sauvegarder automatiquement
                $price = $priceService->forceUpdatePrice($asset->ticker, $asset->currency, $unitType);

                if ($price !== null) {
                    $updated++;
                    $this->info("✓ {$asset->ticker}: {$price} {$asset->currency}");
                } else {
                    $failed++;
                    $this->error("✗ {$asset->ticker}: Failed to fetch price");
                }

                // Petite pause pour éviter les rate limits
                usleep(20000000); // 20s

            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ {$asset->ticker}: Error - ".$e->getMessage());
                Log::error("Price update failed for {$asset->ticker}: ".$e->getMessage());
            }
        }

        $this->info("\nUpdate completed:");
        $this->info("- Updated: {$updated}");
        $this->info("- Failed: {$failed}");
        $this->info("- Skipped: {$skipped}");

        // Synchroniser les prix des positions avec ceux de price_assets
        $this->info("\nSynchronizing position prices...");
        $syncedCount = $this->synchronizePositionPrices();
        $this->info("- Synchronized: {$syncedCount} positions");

        Log::info('Price assets update completed', [
            'updated' => $updated,
            'failed' => $failed,
            'skipped' => $skipped,
            'synchronized' => $syncedCount,
        ]);

        return 0;
    }

    /**
     * Synchronize position prices with price_assets table
     */
    private function synchronizePositionPrices(): int
    {
        $syncedCount = 0;

        \App\Models\WalletPosition::whereNotNull('ticker')->get()->each(function ($position) use (&$syncedCount) {
            $priceAsset = \App\Models\PriceAsset::where('ticker', $position->ticker)->first();

            if ($priceAsset && $priceAsset->price) {
                $position->update(['price' => (string) $priceAsset->price]);
                $syncedCount++;
            }
        });

        return $syncedCount;
    }

    /**
     * Convertir le type d'actif en unitType pour l'API
     */
    private function getUnitTypeFromAssetType(string $assetType): ?string
    {
        return match ($assetType) {
            'CRYPTO', 'TOKEN' => 'CRYPTO',
            'STOCK', 'ETF', 'BOND' => 'STOCK',
            'COMMODITY' => 'COMMODITY',
            default => null,
        };
    }
}
