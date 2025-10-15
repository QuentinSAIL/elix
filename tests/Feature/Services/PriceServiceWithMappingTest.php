<?php

namespace Tests\Feature\Services;

use App\Services\PriceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PriceServiceWithMappingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear();
        // Ensure mapping contains a custom symbol that maps to CoinGecko id
        $mappingPath = resource_path('data/crypto_map.json');
        File::ensureDirectoryExists(dirname($mappingPath));
        File::put($mappingPath, json_encode([
            'HYPE' => 'hyperliquid',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function test_price_service_uses_mapping_and_coingecko_first_for_crypto()
    {
        Http::fake([
            // Alpha/Yahoo should be unused because symbol is detected as crypto
            'https://www.alphavantage.co/*' => Http::response([], 404),
            'https://query1.finance.yahoo.com/*' => Http::response([], 404),
            'https://api.coingecko.com/*' => Http::response([
                'hyperliquid' => ['eur' => 12.34],
            ], 200),
        ]);

        $service = new PriceService;
        $price = $service->getPrice('HYPE', 'EUR');

        $this->assertSame(12.34, $price);
        $this->assertTrue(Cache::has('price_HYPE_EUR'));
    }
}
