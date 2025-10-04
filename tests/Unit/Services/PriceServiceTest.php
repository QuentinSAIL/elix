<?php

namespace Tests\Unit\Services;

use App\Services\PriceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @covers \App\Services\PriceService
 */
class PriceServiceTest extends TestCase
{
    protected PriceService $priceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceService = new PriceService();
        Cache::clear(); // Ensure a clean cache for each test
        Log::swap($this->createMock(\Psr\Log\LoggerInterface::class)); // Mock logger to prevent actual logging
    }

    #[test]
    public function get_price_returns_cached_value_if_available()
    {
        Cache::put('price_BTC_EUR', 50000.0, 300);

        $price = $this->priceService->getPrice('BTC', 'EUR');

        $this->assertEquals(50000.0, $price);
        Http::assertNothingSent(); // No external call should be made
    }

    #[test]
    public function get_price_fetches_from_alpha_vantage_if_not_cached_and_successful()
    {
        Http::fake([
            'https://www.alphavantage.co/*' => Http::response(['Global Quote' => ['05. price' => '45000.0']], 200),
            'https://query1.finance.yahoo.com/*' => Http::response([], 404),
            'https://api.coingecko.com/*' => Http::response([], 404),
        ]);

        $price = $this->priceService->getPrice('AAPL', 'USD');

        $this->assertEquals(45000.0, $price);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.alphavantage.co/query' &&
                   $request['symbol'] === 'AAPL';
        });
        $this->assertTrue(Cache::has('price_AAPL_USD'));
    }

    #[test]
    public function get_price_fetches_from_yahoo_finance_if_alpha_vantage_fails()
    {
        Http::fake([
            'https://www.alphavantage.co/*' => Http::response([], 404),
            'https://query1.finance.yahoo.com/*' => Http::response(['chart' => ['result' => [['meta' => ['regularMarketPrice' => 150.0]]]]], 200),
            'https://api.coingecko.com/*' => Http::response([], 404),
        ]);

        $price = $this->priceService->getPrice('GOOG', 'USD');

        $this->assertEquals(150.0, $price);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'query1.finance.yahoo.com') &&
                   str_contains($request->url(), 'GOOG');
        });
        $this->assertTrue(Cache::has('price_GOOG_USD'));
    }

    #[test]
    public function get_price_fetches_from_coingecko_if_others_fail_for_crypto()
    {
        Http::fake([
            'https://www.alphavantage.co/*' => Http::response([], 404),
            'https://query1.finance.yahoo.com/*' => Http::response([], 404),
            'https://api.coingecko.com/*' => Http::response(['bitcoin' => ['eur' => 40000.0]], 200),
        ]);

        $price = $this->priceService->getPrice('BTC', 'EUR');

        $this->assertEquals(40000.0, $price);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.coingecko.com') &&
                   str_contains($request->url(), 'bitcoin');
        });
        $this->assertTrue(Cache::has('price_BTC_EUR'));
    }

    #[test]
    public function get_price_returns_null_if_all_apis_fail()
    {
        Http::fake(['*' => Http::response([], 404)]);

        $price = $this->priceService->getPrice('UNKNOWN', 'EUR');

        $this->assertNull($price);
        $this->assertFalse(Cache::has('price_UNKNOWN_EUR'));
    }

    #[test]
    public function get_price_returns_null_on_api_exception()
    {
        Http::fake(['*' => Http::timeout(1)->send()]); // Simulate timeout

        $price = $this->priceService->getPrice('TIMEOUT', 'EUR');

        $this->assertNull($price);
        $this->assertFalse(Cache::has('price_TIMEOUT_EUR'));
    }

    #[test]
    public function get_prices_returns_array_of_prices()
    {
        Http::fake([
            'https://www.alphavantage.co/*' => Http::sequence()
                ->push(['Global Quote' => ['05. price' => '100.0']], 200) // For AAPL
                ->push(['Global Quote' => ['05. price' => '200.0']], 200), // For MSFT
            'https://query1.finance.yahoo.com/*' => Http::response([], 404),
            'https://api.coingecko.com/*' => Http::response([], 404),
        ]);

        $prices = $this->priceService->getPrices(['AAPL', 'MSFT'], 'USD');

        $this->assertEquals(['AAPL' => 100.0, 'MSFT' => 200.0], $prices);
    }

    #[test]
    public function calculate_positions_value_calculates_correctly_with_tickers()
    {
        Http::fake([
            'https://www.alphavantage.co/*' => Http::sequence()
                ->push(['Global Quote' => ['05. price' => '100.0']], 200) // For AAPL
                ->push(['Global Quote' => ['05. price' => '200.0']], 200), // For MSFT
            'https://query1.finance.yahoo.com/*' => Http::response([], 404),
            'https://api.coingecko.com/*' => Http::response([], 404),
        ]);

        $positions = [
            ['ticker' => 'AAPL', 'quantity' => 2, 'price' => 90], // Price should be fetched
            ['ticker' => 'MSFT', 'quantity' => 1, 'price' => 180], // Price should be fetched
        ];

        $totalValue = $this->priceService->calculatePositionsValue($positions, 'USD');

        $this->assertEquals(400.0, $totalValue); // (2*100) + (1*200) = 200 + 200 = 400
    }

    #[test]
    public function calculate_positions_value_uses_stocked_price_if_ticker_has_no_current_price()
    {
        Http::fake(['*' => Http::response([], 404)]); // All APIs fail

        $positions = [
            ['ticker' => 'AAPL', 'quantity' => 2, 'price' => 90], // Should use stocked price
            ['ticker' => null, 'quantity' => 3, 'price' => 50], // No ticker, use stocked price
        ];

        $totalValue = $this->priceService->calculatePositionsValue($positions, 'USD');

        $this->assertEquals(330.0, $totalValue); // (2*90) + (3*50) = 180 + 150 = 330
    }

    #[test]
    public function clear_price_cache_clears_specific_cache_key()
    {
        Cache::put('price_BTC_EUR', 50000.0, 300);
        Cache::put('price_ETH_EUR', 2000.0, 300);

        $this->priceService->clearPriceCache('BTC', 'EUR');

        $this->assertFalse(Cache::has('price_BTC_EUR'));
        $this->assertTrue(Cache::has('price_ETH_EUR'));
    }

    #[test]
    public function clear_all_price_cache_clears_all_cache()
    {
        Cache::put('price_BTC_EUR', 50000.0, 300);
        Cache::put('exchange_rate_EUR_USD', 1.1, 3600);

        $this->priceService->clearAllPriceCache();

        $this->assertFalse(Cache::has('price_BTC_EUR'));
        $this->assertFalse(Cache::has('exchange_rate_EUR_USD'));
    }

    #[test]
    public function get_exchange_rate_returns_1_for_same_currencies()
    {
        $rate = $this->priceService->getExchangeRate('EUR', 'EUR');
        $this->assertEquals(1.0, $rate);
        Http::assertNothingSent();
    }

    #[test]
    public function get_exchange_rate_returns_cached_value()
    {
        Cache::put('exchange_rate_EUR_USD', 1.2, 3600);

        $rate = $this->priceService->getExchangeRate('EUR', 'USD');
        $this->assertEquals(1.2, $rate);
        Http::assertNothingSent();
    }

    #[test]
    public function get_exchange_rate_fetches_from_fixer_if_not_cached_and_successful()
    {
        Http::fake([
            'https://api.fixer.io/*' => Http::response(['rates' => ['USD' => 1.1]], 200),
            'https://api.exchangerate-api.com/*' => Http::response([], 404),
        ]);

        $rate = $this->priceService->getExchangeRate('EUR', 'USD');

        $this->assertEquals(1.1, $rate);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.fixer.io') &&
                   $request['base'] === 'EUR' &&
                   $request['symbols'] === 'USD';
        });
        $this->assertTrue(Cache::has('exchange_rate_EUR_USD'));
    }

    #[test]
    public function get_exchange_rate_fetches_from_exchangeratesapi_if_fixer_fails()
    {
        Http::fake([
            'https://api.fixer.io/*' => Http::response([], 404),
            'https://api.exchangerate-api.com/*' => Http::response(['rates' => ['USD' => 1.05]], 200),
        ]);

        $rate = $this->priceService->getExchangeRate('EUR', 'USD');

        $this->assertEquals(1.05, $rate);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.exchangerate-api.com') &&
                   str_contains($request->url(), 'EUR');
        });
        $this->assertTrue(Cache::has('exchange_rate_EUR_USD'));
    }

    #[test]
    public function get_exchange_rate_returns_null_if_all_apis_fail()
    {
        Http::fake(['*' => Http::response([], 404)]);

        $rate = $this->priceService->getExchangeRate('EUR', 'USD');

        $this->assertNull($rate);
        $this->assertFalse(Cache::has('exchange_rate_EUR_USD'));
    }

    #[test]
    public function convert_currency_converts_correctly()
    {
        $this->mock(PriceService::class, function ($mock) {
            $mock->makePartial(); // Allow original methods to be called
            $mock->shouldReceive('getExchangeRate')
                ->with('EUR', 'USD')
                ->andReturn(1.1);
        });

        $convertedAmount = $this->priceService->convertCurrency(100.0, 'EUR', 'USD');
        $this->assertEquals(110.0, $convertedAmount);
    }

    #[test]
    public function convert_currency_returns_null_if_rate_not_found()
    {
        $this->mock(PriceService::class, function ($mock) {
            $mock->makePartial();
            $mock->shouldReceive('getExchangeRate')
                ->with('EUR', 'USD')
                ->andReturn(null);
        });

        $convertedAmount = $this->priceService->convertCurrency(100.0, 'EUR', 'USD');
        $this->assertNull($convertedAmount);
    }

    #[test]
    public function get_price_in_currency_fetches_and_converts_price()
    {
        $this->mock(PriceService::class, function ($mock) {
            $mock->makePartial();
            $mock->shouldReceive('getPrice')
                ->with('AAPL', 'USD')
                ->andReturn(150.0);
            $mock->shouldReceive('convertCurrency')
                ->with(150.0, 'USD', 'EUR')
                ->andReturn(130.0);
        });

        $price = $this->priceService->getPriceInCurrency('AAPL', 'EUR', 'USD');
        $this->assertEquals(130.0, $price);
    }

    #[test]
    public function get_price_in_currency_returns_null_if_price_not_found()
    {
        $this->mock(PriceService::class, function ($mock) {
            $mock->makePartial();
            $mock->shouldReceive('getPrice')
                ->with('AAPL', 'USD')
                ->andReturn(null);
        });

        $price = $this->priceService->getPriceInCurrency('AAPL', 'EUR', 'USD');
        $this->assertNull($price);
    }

    #[test]
    public function calculate_positions_value_in_currency_calculates_correctly()
    {
        $positions = [
            ['ticker' => 'AAPL', 'quantity' => 2, 'price' => 100], // Stocked price 100
            ['ticker' => 'BTC', 'quantity' => 0.5, 'price' => 30000], // Stocked price 30000
            ['ticker' => null, 'quantity' => 10, 'price' => 5], // No ticker, use stocked price
        ];

        $this->mock(PriceService::class, function ($mock) {
            $mock->makePartial();
            // For AAPL
            $mock->shouldReceive('getPriceInCurrency')
                ->with('AAPL', 'EUR', 'USD')
                ->andReturn(150.0); // Converted price
            // For BTC
            $mock->shouldReceive('getPriceInCurrency')
                ->with('BTC', 'EUR', 'USD')
                ->andReturn(40000.0); // Converted price
        });

        $totalValue = $this->priceService->calculatePositionsValueInCurrency($positions, 'EUR');

        // (2 * 150) + (0.5 * 40000) + (10 * 5) = 300 + 20000 + 50 = 20350
        $this->assertEquals(20350.0, $totalValue);
    }

    #[test]
    public function calculate_positions_value_in_currency_uses_fallback_if_price_in_currency_fails()
    {
        $positions = [
            ['ticker' => 'AAPL', 'quantity' => 2, 'price' => 100], // PriceInCurrency fails, Price succeeds
            ['ticker' => 'MSFT', 'quantity' => 1, 'price' => 200], // PriceInCurrency fails, Price fails, use stocked
        ];

        $this->mock(PriceService::class, function ($mock) {
            $mock->makePartial();
            // For AAPL: PriceInCurrency fails, Price succeeds
            $mock->shouldReceive('getPriceInCurrency')
                ->with('AAPL', 'EUR', 'USD')
                ->andReturn(null);
            $mock->shouldReceive('getPrice')
                ->with('AAPL', 'EUR')
                ->andReturn(160.0);

            // For MSFT: PriceInCurrency fails, Price fails
            $mock->shouldReceive('getPriceInCurrency')
                ->with('MSFT', 'EUR', 'USD')
                ->andReturn(null);
            $mock->shouldReceive('getPrice')
                ->with('MSFT', 'EUR')
                ->andReturn(null);
        });

        $totalValue = $this->priceService->calculatePositionsValueInCurrency($positions, 'EUR');

        // (2 * 160) + (1 * 200) = 320 + 200 = 520
        $this->assertEquals(520.0, $totalValue);
    }
}