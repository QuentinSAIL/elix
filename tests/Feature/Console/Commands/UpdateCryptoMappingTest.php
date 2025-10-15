<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateCryptoMappingTest extends TestCase
{
    protected string $mappingPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mappingPath = resource_path('data/crypto_map.json');

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($this->mappingPath));

        // Seed an existing mapping with a manual override that should be preserved
        File::put($this->mappingPath, json_encode([
            'BTC' => 'bitcoin',
            'XBT' => 'bitcoin', // manual alias to keep
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function test_command_writes_and_merges_mapping()
    {
        Http::fake([
            'https://api.coingecko.com/api/v3/coins/markets*' => Http::response([
                ['symbol' => 'btc', 'id' => 'bitcoin'],
                ['symbol' => 'eth', 'id' => 'ethereum'],
                ['symbol' => 'sol', 'id' => 'solana'],
            ], 200),
        ]);

        $exitCode = Artisan::call('crypto:update-mapping', ['--limit' => 3]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->mappingPath);

        $json = json_decode(File::get($this->mappingPath), true);
        $this->assertIsArray($json);
        // Fetched symbols
        $this->assertEquals('bitcoin', $json['BTC']);
        $this->assertEquals('ethereum', $json['ETH']);
        $this->assertEquals('solana', $json['SOL']);
        // Manual override preserved
        $this->assertEquals('bitcoin', $json['XBT']);
    }
}
