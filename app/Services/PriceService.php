<?php

namespace App\Services;

use App\Models\WalletPosition;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceService
{
    private const CACHE_DURATION = 900; // 15 minutes
    private const API_TIMEOUT = 10;
    private const EXCHANGE_CACHE_DURATION = 3600; // 1 hour for exchange rates
    private const RATE_LIMIT_CACHE_DURATION = 300; // 5 minutes for rate limit cooldown
    private const MAX_API_CALLS_PER_MINUTE = 10; // Conservative limit

    /**
     * Get current price for a ticker symbol with intelligent caching and BDD fallback
     */
    public function getPrice(string $ticker, string $currency = 'EUR', ?string $unitType = null): ?float
    {
        $normalizedTicker = strtoupper($ticker);
        $cacheKey = "price_v3_{$normalizedTicker}_{$currency}";

        // Check if we're rate limited for this ticker
        if ($this->isRateLimited($normalizedTicker)) {
            Log::info("Rate limited for {$normalizedTicker}, using BDD fallback");
            return $this->getPriceFromDatabase($normalizedTicker, $currency);
        }

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($normalizedTicker, $currency, $unitType) {
            try {
                // First try to get from database (most recent price)
                $dbPrice = $this->getPriceFromDatabase($normalizedTicker, $currency);

                // If we have a recent price in DB (less than 1 hour old), use it
                if ($dbPrice !== null && $this->hasRecentPriceInDatabase($normalizedTicker)) {
                    Log::info("Using recent DB price for {$normalizedTicker}: {$dbPrice} {$currency}");
                    return $dbPrice;
                }

                // Check API rate limits before making calls
                if ($this->shouldSkipApiCall()) {
                    Log::info("Skipping API call due to rate limits, using DB fallback for {$normalizedTicker}");
                    return $dbPrice;
                }

                // Try to get fresh price from APIs
                $price = $this->fetchPriceFromApis($normalizedTicker, $currency, $unitType);

                if ($price !== null) {
                    Log::info("Fresh price fetched for {$normalizedTicker}: {$price} {$currency}");
                    // Update database with fresh price
                    $this->updatePriceInDatabase($normalizedTicker, $price, $currency);
                    return $price;
                }

                // Fallback to database price
                Log::info("API failed, using DB fallback for {$normalizedTicker}");
                return $dbPrice;

            } catch (\Exception $e) {
                Log::error("Error fetching price for {$normalizedTicker}: ".$e->getMessage());
                return $this->getPriceFromDatabase($normalizedTicker, $currency);
            }
        });
    }

    /**
     * Get prices for multiple tickers with intelligent batching
     */
    public function getPrices(array $tickers, string $currency = 'EUR', ?string $unitType = null): array
    {
        $prices = [];
        $normalizedTickers = array_map('strtoupper', $tickers);

        // First, try to get all prices from cache/database
        foreach ($normalizedTickers as $ticker) {
            $prices[$ticker] = $this->getPrice($ticker, $currency, $unitType);
        }

        return $prices;
    }

    /**
     * Get price from database (most recent price for this ticker)
     */
    private function getPriceFromDatabase(string $ticker, string $currency): ?float
    {
        try {
            $position = WalletPosition::whereRaw('UPPER(ticker) = ?', [strtoupper($ticker)])
                ->whereNotNull('price')
                ->orderBy('updated_at', 'desc')
                ->first();

            return $position ? (float) $position->price : null;
        } catch (\Exception $e) {
            Log::debug("Database error for {$ticker}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if we have a recent price in database (less than 1 hour old)
     */
    private function hasRecentPriceInDatabase(string $ticker): bool
    {
        try {
            $position = WalletPosition::whereRaw('UPPER(ticker) = ?', [strtoupper($ticker)])
                ->whereNotNull('price')
                ->where('updated_at', '>=', now()->subHour())
                ->first();

            return $position !== null;
        } catch (\Exception $e) {
            Log::debug("Database error checking recent price for {$ticker}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update price in database for all positions with this ticker
     */
    private function updatePriceInDatabase(string $ticker, float $price, string $currency): void
    {
        try {
            WalletPosition::whereRaw('UPPER(ticker) = ?', [strtoupper($ticker)])
                ->update(['price' => (string) $price]);
        } catch (\Exception $e) {
            Log::debug("Database error updating price for {$ticker}: " . $e->getMessage());
        }
    }

    /**
     * Check if we're rate limited for a specific ticker
     */
    private function isRateLimited(string $ticker): bool
    {
        try {
            $rateLimitKey = "rate_limit_{$ticker}";
            return Cache::has($rateLimitKey);
        } catch (\Exception $e) {
            Log::debug("Rate limit check error for {$ticker}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set rate limit for a ticker
     */
    private function setRateLimit(string $ticker): void
    {
        try {
            $rateLimitKey = "rate_limit_{$ticker}";
            Cache::put($rateLimitKey, true, self::RATE_LIMIT_CACHE_DURATION);
        } catch (\Exception $e) {
            Log::debug("Rate limit set error for {$ticker}: " . $e->getMessage());
        }
    }

    /**
     * Check if we should skip API calls due to global rate limits
     */
    private function shouldSkipApiCall(): bool
    {
        try {
            $apiCallKey = 'api_calls_count';
            $currentMinute = now()->format('Y-m-d H:i');
            $minuteKey = "api_calls_{$currentMinute}";

            $callsThisMinute = Cache::get($minuteKey, 0);

            if ($callsThisMinute >= self::MAX_API_CALLS_PER_MINUTE) {
                return true;
            }

            // Increment counter
            Cache::put($minuteKey, $callsThisMinute + 1, 60);
            return false;
        } catch (\Exception $e) {
            Log::debug("API call limit check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch price from APIs with proper error handling and rate limiting
     */
    private function fetchPriceFromApis(string $ticker, string $currency, ?string $unitType): ?float
    {
        // Simple logic: crypto/token = CoinGecko, others = traditional APIs
        if ($unitType === 'TOKEN' || $unitType === 'CRYPTO') {
            Log::info("Using crypto APIs for {$ticker} (type: {$unitType})");
            return $this->getPriceFromCoinGecko($ticker, $currency);
        } else {
            Log::info("Using traditional finance APIs for {$ticker} (type: {$unitType})");
            // Try traditional APIs in order
            $price = $this->getPriceFromAlphaVantage($ticker, $currency);
            if ($price !== null) {
                Log::info("Price fetched from alphavantage for {$ticker}: {$price} {$currency}");
                return $price;
            }

            $price = $this->getPriceFromYahooFinance($ticker, $currency);
            if ($price !== null) {
                Log::info("Price fetched from yahoo for {$ticker}: {$price} {$currency}");
                return $price;
            }

            Log::warning("All traditional APIs failed for {$ticker}");
            return null;
        }
    }

    /**
     * Calculate total value of positions with current prices
     */
    public function calculatePositionsValue(array $positions, string $currency = 'EUR'): float
    {
        $totalValue = 0;

        foreach ($positions as $position) {
            if (! $position['ticker']) {
                // For positions without ticker, use stocked price
                $totalValue += (float) $position['quantity'] * (float) $position['price'];

                continue;
            }

            $currentPrice = $this->getPrice($position['ticker'], $currency, $position['unit'] ?? null);
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
                    'apikey' => config('services.alpha_vantage.key', 'demo'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['Global Quote']['05. price'])) {
                    return (float) $data['Global Quote']['05. price'];
                }
            } else {
                $statusCode = $response->status();
                Log::debug("Alpha Vantage API error for {$ticker}: HTTP {$statusCode}");

                // Set rate limit if we get 429
                if ($statusCode === 429) {
                    $this->setRateLimit($ticker);
                }
            }
        } catch (\Exception $e) {
            Log::debug("Alpha Vantage API error for {$ticker}: ".$e->getMessage());
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
            } else {
                $statusCode = $response->status();
                Log::debug("Yahoo Finance API error for {$ticker}: HTTP {$statusCode}");

                // Set rate limit if we get 429
                if ($statusCode === 429) {
                    $this->setRateLimit($ticker);
                }
            }
        } catch (\Exception $e) {
            Log::debug("Yahoo Finance API error for {$ticker}: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Get price from CoinGecko (for cryptocurrencies)
     */
    private function getPriceFromCoinGecko(string $ticker, string $currency): ?float
    {
        try {
            $normalizedCurrency = strtolower($currency);

            // Get CoinGecko ID - try common mappings first
            $coingeckoId = $this->getCoinGeckoId($ticker);

            Log::info("CoinGecko: Requesting {$ticker} with ID {$coingeckoId} in {$currency}");

            $response = Http::timeout(self::API_TIMEOUT)
                ->get('https://api.coingecko.com/api/v3/simple/price', [
                    'ids' => $coingeckoId,
                    'vs_currencies' => $normalizedCurrency,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("CoinGecko response for {$ticker}: " . json_encode($data));

                // Check if we have data for the CoinGecko ID
                if (isset($data[$coingeckoId][$normalizedCurrency])) {
                    $price = (float) $data[$coingeckoId][$normalizedCurrency];
                    Log::info("CoinGecko: Found price for {$ticker}: {$price} {$currency}");
                    return $price;
                } else {
                    Log::warning("CoinGecko: No price data found for {$ticker} (ID: {$coingeckoId}) in {$currency}");
                }
            } else {
                $statusCode = $response->status();
                Log::warning("CoinGecko: HTTP error for {$ticker}: {$statusCode}");

                // Set rate limit if we get 429
                if ($statusCode === 429) {
                    $this->setRateLimit($ticker);
                }
            }
        } catch (\Exception $e) {
            Log::debug("CoinGecko API error for {$ticker}: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Get CoinGecko ID for a ticker
     */
    private function getCoinGeckoId(string $ticker): string
    {
        $ticker = strtoupper($ticker);

        // Common CoinGecko ID mappings
        $mappings = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'USDT' => 'tether',
            'USDC' => 'usd-coin',
            'SOL' => 'solana',
            'ADA' => 'cardano',
            'DOT' => 'polkadot',
            'LINK' => 'chainlink',
            'UNI' => 'uniswap',
            'AAVE' => 'aave',
            'DAI' => 'dai',
            'ZEN' => 'horizen',
            'BNB' => 'binancecoin',
            'XRP' => 'ripple',
            'DOGE' => 'dogecoin',
            'TRX' => 'tron',
            'AVAX' => 'avalanche-2',
            'MATIC' => 'matic-network',
            'LTC' => 'litecoin',
            'BCH' => 'bitcoin-cash',
            'XLM' => 'stellar',
            'ATOM' => 'cosmos',
            'NEAR' => 'near',
            'ALGO' => 'algorand',
            'VET' => 'vechain',
            'ICP' => 'internet-computer',
            'FTM' => 'fantom',
            'HBAR' => 'hedera-hashgraph',
            'CRO' => 'crypto-com-chain',
            'QNT' => 'quant-network',
        ];

        return $mappings[$ticker] ?? strtolower($ticker);
    }




    /**
     * Clear price cache for a specific ticker
     */
    public function clearPriceCache(string $ticker, string $currency = 'EUR'): void
    {
        try {
            $normalizedTicker = strtoupper($ticker);
            $cacheKey = "price_v3_{$normalizedTicker}_{$currency}";
            Cache::forget($cacheKey);

            // Also clear rate limit cache
            $rateLimitKey = "rate_limit_{$normalizedTicker}";
            Cache::forget($rateLimitKey);
        } catch (\Exception $e) {
            Log::debug("Cache clear error for {$ticker}: " . $e->getMessage());
        }
    }

    /**
     * Clear all price cache
     */
    public function clearAllPriceCache(): void
    {
        Cache::flush();
    }

    /**
     * Force update price from APIs (ignore cache and recent DB data)
     */
    public function forceUpdatePrice(string $ticker, string $currency = 'EUR', ?string $unitType = null): ?float
    {
        $normalizedTicker = strtoupper($ticker);

        Log::info("Force updating price for {$normalizedTicker} in {$currency}");

        // Clear cache for this ticker
        $this->clearPriceCache($normalizedTicker, $currency);

        // Try to get fresh price from APIs
        $price = $this->fetchPriceFromApis($normalizedTicker, $currency, $unitType);

        if ($price !== null) {
            Log::info("Force update successful for {$normalizedTicker}: {$price} {$currency}");
            // Update database with fresh price
            $this->updatePriceInDatabase($normalizedTicker, $price, $currency);
            return $price;
        }

        Log::warning("Force update failed for {$normalizedTicker}");
        return null;
    }

    /**
     * Force update all prices (ignore cache and recent DB data)
     */
    public function forceUpdateAllPrices(): array
    {
        Log::info("Starting force update of all prices");

        try {
            // Get all unique tickers from database
            $uniqueTickers = WalletPosition::whereNotNull('ticker')
                ->selectRaw('UPPER(ticker) as ticker')
                ->distinct()
                ->pluck('ticker')
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Database error getting unique tickers: " . $e->getMessage());
            return [
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
                'tickers' => [],
                'error' => 'Database connection failed'
            ];
        }

        $results = [
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'tickers' => []
        ];

        foreach ($uniqueTickers as $ticker) {
            try {
                // Check if we should skip this ticker due to rate limits
                if ($this->isRateLimited($ticker)) {
                    $results['skipped']++;
                    $results['tickers'][$ticker] = 'rate_limited';
                    continue;
                }

                // Force update (ignore recent data check)
                $price = $this->fetchPriceFromApis($ticker, 'EUR', null);

                if ($price !== null) {
                    $this->updatePriceInDatabase($ticker, $price, 'EUR');
                    $results['updated']++;
                    $results['tickers'][$ticker] = $price;
                    Log::info("Force updated price for {$ticker}: {$price} EUR");
                } else {
                    $results['failed']++;
                    $results['tickers'][$ticker] = 'api_failed';
                    Log::warning("Force update failed for {$ticker}");
                }

                // Small delay to avoid rate limiting
                usleep(200000); // 200ms delay for force updates

            } catch (\Exception $e) {
                $results['failed']++;
                $results['tickers'][$ticker] = 'error: ' . $e->getMessage();
                Log::error("Error force updating price for {$ticker}: " . $e->getMessage());
            }
        }

        Log::info("Force update completed", $results);
        return $results;
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
                Log::error("Error fetching exchange rate for {$fromCurrency} to {$toCurrency}: ".$e->getMessage());

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
    public function getPriceInCurrency(string $ticker, string $userCurrency, string $originalCurrency = 'USD', ?string $unitType = null): ?float
    {
        // First get the price in the original currency (usually USD for stocks)
        $price = $this->getPrice($ticker, $originalCurrency, $unitType);

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
            if (! $position['ticker']) {
                // For positions without ticker, assume they're already in user's currency
                $totalValue += (float) $position['quantity'] * (float) $position['price'];

                continue;
            }

            // Try to get price in user's currency
            $currentPrice = $this->getPriceInCurrency($position['ticker'], $userCurrency, 'USD', $position['unit'] ?? null);

            if ($currentPrice !== null) {
                $totalValue += (float) $position['quantity'] * $currentPrice;
            } else {
                // Fallback: try direct price fetch in user's currency
                $currentPrice = $this->getPrice($position['ticker'], $userCurrency, $position['unit'] ?? null);
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
                    'symbols' => $toCurrency,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rates'][$toCurrency])) {
                    return (float) $data['rates'][$toCurrency];
                }
            }
        } catch (\Exception $e) {
            Log::debug("Fixer API error for {$fromCurrency} to {$toCurrency}: ".$e->getMessage());
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
            Log::debug("ExchangeRates-API error for {$fromCurrency} to {$toCurrency}: ".$e->getMessage());
        }

        return null;
    }
}
