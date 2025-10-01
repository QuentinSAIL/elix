<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceService
{
    private const CACHE_DURATION = 300; // 5 minutes
    private const API_TIMEOUT = 10;
    private const EXCHANGE_CACHE_DURATION = 3600; // 1 hour for exchange rates

    /**
     * Get current price for a ticker symbol
     */
    public function getPrice(string $ticker, string $currency = 'EUR'): ?float
    {
        $cacheKey = "price_{$ticker}_{$currency}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($ticker, $currency) {
            try {
                // Try multiple free APIs
                $price = $this->getPriceFromAlphaVantage($ticker, $currency)
                    ?? $this->getPriceFromYahooFinance($ticker, $currency)
                    ?? $this->getPriceFromCoinGecko($ticker, $currency);

                if ($price !== null) {
                    Log::info("Price fetched for {$ticker}: {$price} {$currency}");
                    return $price;
                }

                Log::warning("No price found for ticker: {$ticker}");
                return null;
            } catch (\Exception $e) {
                Log::error("Error fetching price for {$ticker}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get prices for multiple tickers
     */
    public function getPrices(array $tickers, string $currency = 'EUR'): array
    {
        $prices = [];

        foreach ($tickers as $ticker) {
            $prices[$ticker] = $this->getPrice($ticker, $currency);
        }

        return $prices;
    }

    /**
     * Calculate total value of positions with current prices
     */
    public function calculatePositionsValue(array $positions, string $currency = 'EUR'): float
    {
        $totalValue = 0;

        foreach ($positions as $position) {
            if (!$position['ticker']) {
                // For positions without ticker, use stocked price
                $totalValue += (float) $position['quantity'] * (float) $position['price'];
                continue;
            }

            $currentPrice = $this->getPrice($position['ticker'], $currency);
            if ($currentPrice !== null) {
                $totalValue += (float) $position['quantity'] * $currentPrice;
            } else {
                // Fallback to stocked price if current price not available
                $totalValue += (float) $position['quantity'] * (float) $position['price'];
            }
        }

        return $totalValue;
    }

    /**
     * Get price from Alpha Vantage (free tier: 5 calls per minute)
     */
    private function getPriceFromAlphaVantage(string $ticker, string $currency): ?float
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->get('https://www.alphavantage.co/query', [
                    'function' => 'GLOBAL_QUOTE',
                    'symbol' => $ticker,
                    'apikey' => config('services.alpha_vantage.key', 'demo')
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['Global Quote']['05. price'])) {
                    return (float) $data['Global Quote']['05. price'];
                }
            }
        } catch (\Exception $e) {
            Log::debug("Alpha Vantage API error for {$ticker}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get price from Yahoo Finance (unofficial API)
     */
    private function getPriceFromYahooFinance(string $ticker, string $currency): ?float
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                    return (float) $data['chart']['result'][0]['meta']['regularMarketPrice'];
                }
            }
        } catch (\Exception $e) {
            Log::debug("Yahoo Finance API error for {$ticker}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get price from CoinGecko (for cryptocurrencies)
     */
    private function getPriceFromCoinGecko(string $ticker, string $currency): ?float
    {
        try {
            // Map common crypto tickers to CoinGecko IDs
            $cryptoMapping = [
                'BTC' => 'bitcoin',
                'ETH' => 'ethereum',
                'ADA' => 'cardano',
                'DOT' => 'polkadot',
                'LINK' => 'chainlink',
                'UNI' => 'uniswap',
                'AAVE' => 'aave',
                'COMP' => 'compound-governance-token',
            ];

            $coinId = $cryptoMapping[strtoupper($ticker)] ?? strtolower($ticker);

            $response = Http::timeout(self::API_TIMEOUT)
                ->get("https://api.coingecko.com/api/v3/simple/price", [
                    'ids' => $coinId,
                    'vs_currencies' => strtolower($currency)
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data[$coinId][strtolower($currency)])) {
                    return (float) $data[$coinId][strtolower($currency)];
                }
            }
        } catch (\Exception $e) {
            Log::debug("CoinGecko API error for {$ticker}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Clear price cache for a specific ticker
     */
    public function clearPriceCache(string $ticker, string $currency = 'EUR'): void
    {
        $cacheKey = "price_{$ticker}_{$currency}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear all price cache
     */
    public function clearAllPriceCache(): void
    {
        Cache::flush();
    }

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";

        return Cache::remember($cacheKey, self::EXCHANGE_CACHE_DURATION, function () use ($fromCurrency, $toCurrency) {
            try {
                $rate = $this->getExchangeRateFromFixer($fromCurrency, $toCurrency)
                    ?? $this->getExchangeRateFromExchangeRatesAPI($fromCurrency, $toCurrency);

                if ($rate !== null) {
                    Log::info("Exchange rate fetched: 1 {$fromCurrency} = {$rate} {$toCurrency}");
                    return $rate;
                }

                Log::warning("No exchange rate found for {$fromCurrency} to {$toCurrency}");
                return null;
            } catch (\Exception $e) {
                Log::error("Error fetching exchange rate for {$fromCurrency} to {$toCurrency}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Convert amount from one currency to another
     */
    public function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);

        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    /**
     * Get price converted to user's preferred currency
     */
    public function getPriceInCurrency(string $ticker, string $userCurrency, string $originalCurrency = 'USD'): ?float
    {
        // First get the price in the original currency (usually USD for stocks)
        $price = $this->getPrice($ticker, $originalCurrency);

        if ($price === null) {
            return null;
        }

        // Convert to user's preferred currency
        return $this->convertCurrency($price, $originalCurrency, $userCurrency);
    }

    /**
     * Calculate total value of positions with current prices in user's preferred currency
     */
    public function calculatePositionsValueInCurrency(array $positions, string $userCurrency): float
    {
        $totalValue = 0;

        foreach ($positions as $position) {
            if (!$position['ticker']) {
                // For positions without ticker, assume they're already in user's currency
                $totalValue += (float) $position['quantity'] * (float) $position['price'];
                continue;
            }

            // Try to get price in user's currency
            $currentPrice = $this->getPriceInCurrency($position['ticker'], $userCurrency, 'USD');

            if ($currentPrice !== null) {
                $totalValue += (float) $position['quantity'] * $currentPrice;
            } else {
                // Fallback: try direct price fetch in user's currency
                $currentPrice = $this->getPrice($position['ticker'], $userCurrency);
                if ($currentPrice !== null) {
                    $totalValue += (float) $position['quantity'] * $currentPrice;
                } else {
                    // Final fallback to stocked price (assume it's in user's currency)
                    $totalValue += (float) $position['quantity'] * (float) $position['price'];
                }
            }
        }

        return $totalValue;
    }

    /**
     * Get exchange rate from Fixer.io (free tier: 100 requests/month)
     */
    private function getExchangeRateFromFixer(string $fromCurrency, string $toCurrency): ?float
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->get('https://api.fixer.io/latest', [
                    'access_key' => config('services.fixer.key', ''),
                    'base' => $fromCurrency,
                    'symbols' => $toCurrency
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rates'][$toCurrency])) {
                    return (float) $data['rates'][$toCurrency];
                }
            }
        } catch (\Exception $e) {
            Log::debug("Fixer API error for {$fromCurrency} to {$toCurrency}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get exchange rate from ExchangeRates-API (free tier: 1000 requests/month)
     */
    private function getExchangeRateFromExchangeRatesAPI(string $fromCurrency, string $toCurrency): ?float
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->get("https://api.exchangerate-api.com/v4/latest/{$fromCurrency}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rates'][$toCurrency])) {
                    return (float) $data['rates'][$toCurrency];
                }
            }
        } catch (\Exception $e) {
            Log::debug("ExchangeRates-API error for {$fromCurrency} to {$toCurrency}: " . $e->getMessage());
        }

        return null;
    }
}
