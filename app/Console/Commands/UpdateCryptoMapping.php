<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateCryptoMapping extends Command
{
    protected $signature = 'crypto:update-mapping {--limit=300 : Number of top coins to fetch}';

    protected $description = 'Fetch top cryptocurrencies from CoinGecko and update symbol->id mapping JSON';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        if ($limit < 1 || $limit > 500) {
            $this->warn('Limit must be between 1 and 500. Using 300.');
            $limit = 300;
        }

        $this->info("Fetching top {$limit} cryptocurrencies from CoinGecko...");

        try {
            $response = Http::timeout(20)->get('https://api.coingecko.com/api/v3/coins/markets', [
                'vs_currency' => 'usd',
                'order' => 'market_cap_desc',
                'per_page' => $limit,
                'page' => 1,
                'sparkline' => 'false',
                'locale' => 'en',
            ]);

            if (! $response->successful()) {
                $this->error('Failed to fetch data from CoinGecko: HTTP '.$response->status());

                return 1;
            }

            $data = $response->json();
            $map = [];
            foreach ($data as $coin) {
                if (! isset($coin['symbol'], $coin['id'])) {
                    continue;
                }
                $symbol = strtoupper(trim($coin['symbol']));
                $id = strtolower(trim($coin['id']));
                $map[$symbol] = $id;
            }

            // Merge existing file to preserve manual overrides
            $filePath = resource_path('data/crypto_map.json');
            if (is_file($filePath)) {
                try {
                    $existing = json_decode((string) file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);
                    foreach ($existing as $sym => $id) {
                        $symU = strtoupper($sym);
                        if (! isset($map[$symU])) {
                            $map[$symU] = strtolower($id);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->warn('Could not parse existing mapping, will overwrite.');
                }
            }

            ksort($map);
            $json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // Ensure directory exists
            $dir = dirname($filePath);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            file_put_contents($filePath, $json."\n");

            $this->info('Mapping written to '.$filePath.' ('.count($map).' symbols).');

            // Advise cache clear
            $this->line('Tip: clear mapping cache if needed: php artisan cache:forget crypto_mapping_v1');

            return 0;
        } catch (\Throwable $e) {
            Log::error('crypto:update-mapping failed: '.$e->getMessage());
            $this->error('Unexpected error: '.$e->getMessage());

            return 1;
        }
    }
}
