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
}
