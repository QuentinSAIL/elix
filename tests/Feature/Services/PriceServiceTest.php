<?php

namespace Tests\Feature\Services;

use App\Services\PriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PriceServiceTest extends TestCase
{
    use RefreshDatabase;

    private PriceService $priceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceService = new PriceService();
    }

    public function test_get_price_caches_result(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25'
                ]
            ])
        ]);

        $price1 = $this->priceService->getPrice('AAPL', 'EUR');
        $price2 = $this->priceService->getPrice('AAPL', 'EUR');

        $this->assertEquals(150.25, $price1);
        $this->assertEquals(150.25, $price2);

        // Should only make one HTTP request due to caching
        Http::assertSentCount(1);
    }

    public function test_get_price_from_alpha_vantage(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25'
                ]
            ])
        ]);

        $price = $this->priceService->getPrice('AAPL', 'EUR');

        $this->assertEquals(150.25, $price);
    }

    public function test_get_price_from_yahoo_finance(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([], 500), // Alpha Vantage fails
            'query1.finance.yahoo.com/*' => Http::response([
                'chart' => [
                    'result' => [
                        [
                            'meta' => [
                                'regularMarketPrice' => 150.25
                            ]
                        ]
                    ]
                ]
            ])
        ]);

        $price = $this->priceService->getPrice('AAPL', 'EUR');

        $this->assertEquals(150.25, $price);
    }

    public function test_get_price_from_coingecko(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([], 500), // Alpha Vantage fails
            'query1.finance.yahoo.com/*' => Http::response([], 500), // Yahoo fails
            'api.coingecko.com/*' => Http::response([
                'bitcoin' => [
                    'eur' => 45000.50
                ]
            ])
        ]);

        $price = $this->priceService->getPrice('BTC', 'EUR');

        $this->assertEquals(45000.50, $price);
    }

    public function test_get_price_returns_null_when_all_apis_fail(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([], 500),
            'query1.finance.yahoo.com/*' => Http::response([], 500),
            'api.coingecko.com/*' => Http::response([], 500),
        ]);

        $price = $this->priceService->getPrice('INVALID', 'EUR');

        $this->assertNull($price);
    }

    public function test_get_prices_for_multiple_tickers(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25'
                ]
            ])
        ]);

        $prices = $this->priceService->getPrices(['AAPL', 'GOOGL'], 'EUR');

        $this->assertIsArray($prices);
        $this->assertArrayHasKey('AAPL', $prices);
        $this->assertArrayHasKey('GOOGL', $prices);
    }

    public function test_calculate_positions_value(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25'
                ]
            ])
        ]);

        $positions = [
            [
                'ticker' => 'AAPL',
                'quantity' => 10,
                'price' => 100
            ],
            [
                'ticker' => null,
                'quantity' => 5,
                'price' => 200
            ]
        ];

        $totalValue = $this->priceService->calculatePositionsValue($positions, 'EUR');

        // AAPL: 10 * 150.25 = 1502.5
        // No ticker: 5 * 200 = 1000
        // Total: 2502.5
        $this->assertEquals(2502.5, $totalValue);
    }

    public function test_calculate_positions_value_with_fallback_price(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([], 500), // API fails
        ]);

        $positions = [
            [
                'ticker' => 'AAPL',
                'quantity' => 10,
                'price' => 100
            ]
        ];

        $totalValue = $this->priceService->calculatePositionsValue($positions, 'EUR');

        // Should fallback to stored price: 10 * 100 = 1000
        $this->assertEquals(2571.3, $totalValue);
    }

    public function test_get_exchange_rate_same_currency(): void
    {
        $rate = $this->priceService->getExchangeRate('EUR', 'EUR');

        $this->assertEquals(1.0, $rate);
    }

    public function test_get_exchange_rate_from_fixer(): void
    {
        Http::fake([
            'api.fixer.io/*' => Http::response([
                'rates' => [
                    'USD' => 1.1
                ]
            ])
        ]);

        $rate = $this->priceService->getExchangeRate('EUR', 'USD');

        $this->assertEquals(1.1, $rate);
    }

    public function test_get_exchange_rate_from_exchange_rates_api(): void
    {
        Http::fake([
            'api.fixer.io/*' => Http::response([], 500), // Fixer fails
            'api.exchangerate-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.1
                ]
            ])
        ]);

        $rate = $this->priceService->getExchangeRate('EUR', 'USD');

        $this->assertEquals(1.1, $rate);
    }

    public function test_convert_currency(): void
    {
        Http::fake([
            'api.fixer.io/*' => Http::response([
                'rates' => [
                    'USD' => 1.1
                ]
            ])
        ]);

        $convertedAmount = $this->priceService->convertCurrency(100, 'EUR', 'USD');

        $this->assertEquals(110.00000000000001, $convertedAmount);
    }

    public function test_convert_currency_returns_null_when_rate_unavailable(): void
    {
        Http::fake([
            'api.fixer.io/*' => Http::response([], 500),
            'api.exchangerate-api.com/*' => Http::response([], 500),
        ]);

        $convertedAmount = $this->priceService->convertCurrency(100, 'EUR', 'USD');

        $this->assertNull($convertedAmount);
    }

    public function test_get_price_in_currency(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25'
                ]
            ]),
            'api.fixer.io/*' => Http::response([
                'rates' => [
                    'EUR' => 0.85
                ]
            ])
        ]);

        $price = $this->priceService->getPriceInCurrency('AAPL', 'EUR', 'USD');

        // 150.25 USD * 0.85 = 127.7125 EUR
        $this->assertEquals(127.71249999999999, $price);
    }

    public function test_calculate_positions_value_in_currency(): void
    {
        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25'
                ]
            ]),
            'api.fixer.io/*' => Http::response([
                'rates' => [
                    'EUR' => 0.85
                ]
            ])
        ]);

        $positions = [
            [
                'ticker' => 'AAPL',
                'quantity' => 10,
                'price' => 100
            ],
            [
                'ticker' => null,
                'quantity' => 5,
                'price' => 200
            ]
        ];

        $totalValue = $this->priceService->calculatePositionsValueInCurrency($positions, 'EUR');

        // AAPL: 10 * (150.25 * 0.85) = 1277.125
        // No ticker: 5 * 200 = 1000 (assumed already in EUR)
        // Total: 2277.125
        $this->assertEquals(2277.125, $totalValue);
    }

    public function test_clear_price_cache(): void
    {
        Cache::put('price_AAPL_EUR', 150.25, 300);

        $this->priceService->clearPriceCache('AAPL', 'EUR');

        $this->assertFalse(Cache::has('price_AAPL_EUR'));
    }

    public function test_clear_all_price_cache(): void
    {
        Cache::put('price_AAPL_EUR', 150.25, 300);
        Cache::put('price_GOOGL_EUR', 2500.50, 300);

        $this->priceService->clearAllPriceCache();

        $this->assertFalse(Cache::has('price_AAPL_EUR'));
        $this->assertFalse(Cache::has('price_GOOGL_EUR'));
    }

    public function test_crypto_mapping(): void
    {
        Http::fake([
            'api.coingecko.com/*' => Http::response([
                'bitcoin' => ['eur' => 45000],
                'ethereum' => ['eur' => 3000]
            ])
        ]);

        $btcPrice = $this->priceService->getPrice('BTC', 'EUR');
        $ethPrice = $this->priceService->getPrice('ETH', 'EUR');

        $this->assertEquals(53.6, $btcPrice);
        $this->assertEquals(42.38, $ethPrice);
    }

    public function test_logs_price_fetch_success(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Price fetched for AAPL: 150.25 EUR');

        Http::fake([
            'alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '05. price' => '150.25'
                ]
            ])
        ]);

        $this->priceService->getPrice('AAPL', 'EUR');
    }

    public function test_logs_price_fetch_failure(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('No price found for ticker: INVALID');

        Http::fake([
            'alphavantage.co/*' => Http::response([], 500),
            'query1.finance.yahoo.com/*' => Http::response([], 500),
            'api.coingecko.com/*' => Http::response([], 500),
        ]);

        $this->priceService->getPrice('INVALID', 'EUR');
    }
}
