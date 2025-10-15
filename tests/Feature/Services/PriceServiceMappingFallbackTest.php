<?php

namespace Tests\Feature\Services;

use App\Services\PriceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PriceServiceMappingFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear();

        // Write malformed JSON to trigger fallback path
        $mappingPath = resource_path('data/crypto_map.json');
        File::ensureDirectoryExists(dirname($mappingPath));
        File::put($mappingPath, '{malformed-json');
    }

    public function test_uses_default_mapping_when_json_is_invalid()
    {
        Http::fake([
            'https://api.coingecko.com/*' => Http::response([
                'bitcoin' => ['eur' => 123.45],
            ], 200),
            // Ensure other providers are not used in this scenario
            'https://www.alphavantage.co/*' => Http::response([], 404),
            'https://query1.finance.yahoo.com/*' => Http::response([], 404),
        ]);

        $service = new PriceService();
        $price = $service->getPrice('BTC', 'EUR');

        $this->assertSame(123.45, $price);
        $this->assertTrue(Cache::has('price_BTC_EUR'));
    }
}
