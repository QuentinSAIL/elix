<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateCryptoMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_handles_invalid_limit()
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([
                ['symbol' => 'BTC', 'id' => 'bitcoin'],
            ], 200),
        ]);

        $this->artisan('crypto:update-mapping', ['--limit' => 0])
            ->expectsOutput('Limit must be between 1 and 500. Using 300.')
            ->assertExitCode(0);
    }

    public function test_command_handles_limit_too_high()
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([
                ['symbol' => 'BTC', 'id' => 'bitcoin'],
            ], 200),
        ]);

        $this->artisan('crypto:update-mapping', ['--limit' => 1000])
            ->expectsOutput('Limit must be between 1 and 500. Using 300.')
            ->assertExitCode(0);
    }

    public function test_command_handles_api_failure()
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([], 500),
        ]);

        $this->artisan('crypto:update-mapping')
            ->expectsOutput('Failed to fetch data from CoinGecko: HTTP 500')
            ->assertExitCode(1);
    }

    public function test_command_successfully_updates_mapping()
    {
        $mockData = [
            ['symbol' => 'BTC', 'id' => 'bitcoin'],
            ['symbol' => 'ETH', 'id' => 'ethereum'],
            ['symbol' => 'ADA', 'id' => 'cardano'],
        ];

        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response($mockData, 200),
        ]);

        $this->artisan('crypto:update-mapping', ['--limit' => 3])
            ->expectsOutput('Fetching top 3 cryptocurrencies from CoinGecko...')
            ->assertExitCode(0);

        // Verify the mapping file was updated
        $this->assertFileExists(resource_path('data/crypto_map.json'));
        $mapping = json_decode(file_get_contents(resource_path('data/crypto_map.json')), true);
        $this->assertArrayHasKey('BTC', $mapping);
        $this->assertArrayHasKey('ETH', $mapping);
        $this->assertArrayHasKey('ADA', $mapping);
        $this->assertEquals('bitcoin', $mapping['BTC']);
        $this->assertEquals('ethereum', $mapping['ETH']);
        $this->assertEquals('cardano', $mapping['ADA']);
    }

    public function test_command_handles_empty_response()
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([], 200),
        ]);

        $this->artisan('crypto:update-mapping')
            ->expectsOutput('Fetching top 300 cryptocurrencies from CoinGecko...')
            ->assertExitCode(0);
    }

    public function test_command_handles_network_error()
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([], 500),
        ]);

        $this->artisan('crypto:update-mapping')
            ->expectsOutput('Fetching top 300 cryptocurrencies from CoinGecko...')
            ->expectsOutput('Failed to fetch data from CoinGecko: HTTP 500')
            ->assertExitCode(1);
    }

    public function test_command_handles_invalid_json_response()
    {
        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response('invalid json', 200),
        ]);

        $this->artisan('crypto:update-mapping')
            ->expectsOutput('Fetching top 300 cryptocurrencies from CoinGecko...')
            ->assertExitCode(1);
    }

    public function test_command_handles_missing_symbol_field()
    {
        $mockData = [
            ['id' => 'bitcoin'], // Missing symbol
            ['symbol' => 'ETH', 'id' => 'ethereum'],
        ];

        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response($mockData, 200),
        ]);

        $this->artisan('crypto:update-mapping', ['--limit' => 2])
            ->expectsOutput('Fetching top 2 cryptocurrencies from CoinGecko...')
            ->assertExitCode(0);

        // Verify mapping file was created/updated
        $this->assertFileExists(resource_path('data/crypto_map.json'));
    }

    public function test_command_handles_missing_id_field()
    {
        $mockData = [
            ['symbol' => 'BTC'], // Missing id
            ['symbol' => 'ETH', 'id' => 'ethereum'],
        ];

        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response($mockData, 200),
        ]);

        $this->artisan('crypto:update-mapping', ['--limit' => 2])
            ->expectsOutput('Fetching top 2 cryptocurrencies from CoinGecko...')
            ->assertExitCode(0);

        // Verify mapping file was created/updated
        $this->assertFileExists(resource_path('data/crypto_map.json'));
    }

    public function test_command_uses_default_limit()
    {
        $mockData = array_fill(0, 300, ['symbol' => 'BTC', 'id' => 'bitcoin']);

        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response($mockData, 200),
        ]);

        $this->artisan('crypto:update-mapping')
            ->expectsOutput('Fetching top 300 cryptocurrencies from CoinGecko...')
            ->assertExitCode(0);
    }

    public function test_command_handles_custom_limit()
    {
        $mockData = array_fill(0, 50, ['symbol' => 'BTC', 'id' => 'bitcoin']);

        Http::fake([
            'api.coingecko.com/api/v3/coins/markets*' => Http::response($mockData, 200),
        ]);

        $this->artisan('crypto:update-mapping', ['--limit' => 50])
            ->expectsOutput('Fetching top 50 cryptocurrencies from CoinGecko...')
            ->assertExitCode(0);
    }
}
