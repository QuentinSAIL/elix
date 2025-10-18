<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $wallet_id
 * @property string $name
 * @property string|null $ticker
 * @property string $unit
 * @property string $quantity
 * @property string $price
 * @property-read \App\Models\Wallet $wallet
 *
 * @method bool updateCurrentPrice()
 */
class WalletPosition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'wallet_id',
        'name',
        'ticker',
        'unit',
        'quantity',
        'price',
        'created_at',
        'updated_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'quantity' => 'decimal:18',
        'price' => 'decimal:18',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Update the current market price for this position and synchronize with other positions having the same ticker
     */
    public function updateCurrentPrice(): bool
    {
        if (! $this->ticker) {
            return false;
        }

        $priceService = app(\App\Services\PriceService::class);
        $currentPrice = $priceService->getPrice($this->ticker, $this->wallet->unit, $this->unit);

        if ($currentPrice !== null) {
            // Update this position
            $this->update(['price' => (string) $currentPrice]);

            // Synchronize with other positions having the same ticker
            $this->synchronizeTickerPrices($currentPrice);

            return true;
        }

        return false;
    }

    /**
     * Synchronize prices for all positions with the same ticker
     */
    private function synchronizeTickerPrices(float $currentPrice): void
    {
        if (! $this->ticker) {
            return;
        }

        $ticker = strtoupper($this->ticker);

        // Find all positions with the same ticker in the same wallet
        $sameTickerPositions = self::where('wallet_id', $this->wallet_id)
            ->whereRaw('UPPER(ticker) = ?', [$ticker])
            ->where('id', '!=', $this->id)
            ->get();

        // Update their prices
        foreach ($sameTickerPositions as $position) {
            $position->update(['price' => (string) $currentPrice]);
        }
    }

    /**
     * Get the current market price (from API) or stored price
     */
    public function getCurrentPrice(): float
    {
        if ($this->ticker) {
            $priceService = app(\App\Services\PriceService::class);
            $currentPrice = $priceService->getPrice($this->ticker, $this->wallet->unit, $this->unit);

            if ($currentPrice !== null) {
                // Update the stored price with the current market price
                $this->update(['price' => (string) $currentPrice]);

                return $currentPrice;
            }
        }

        return (float) $this->price;
    }

    /**
     * Calculate the total value of this position
     */
    public function getValue(): float
    {
        return (float) $this->quantity * (float) $this->price;
    }

    /**
     * Get formatted value for display
     */
    public function getFormattedValue(): string
    {
        return rtrim(rtrim((string) $this->getValue(), '0'), '.');
    }

    /**
     * Get stored value for this position (using stored price, no API calls)
     */
    public function getStoredValue(): float
    {
        return (float) $this->quantity * (float) $this->price;
    }

    /**
     * Get current market value for this position in user's preferred currency
     * Uses stored price first, only makes API call if no stored price exists
     * Prevents multiple simultaneous API calls for the same ticker
     */
    public function getCurrentMarketValue(?string $userCurrency = null): float
    {
        // Use provided currency or wallet currency or EUR as fallback
        if ($userCurrency !== null) {
            $currency = $userCurrency;
        } elseif ($this->wallet !== null) {
            $currency = $this->wallet->unit;
        } else {
            $currency = 'EUR';
        }

        // First, try to use stored price if available
        if ($this->price && (float) $this->price > 0) {
            return (float) $this->quantity * (float) $this->price;
        }

        // If no stored price and we have a ticker, try to get from price_assets table
        if ($this->ticker) {
            $priceAsset = \App\Models\PriceAsset::where('ticker', $this->ticker)->first();

            if ($priceAsset && $priceAsset->price && $priceAsset->isPriceRecent()) {
                // Update this position with the price from price_assets
                $this->update(['price' => (string) $priceAsset->price]);
                return (float) $this->quantity * (float) $priceAsset->price;
            }

            // Check if we're already fetching this ticker (prevent multiple simultaneous calls)
            $fetchingKey = "fetching_price_{$this->ticker}";
            $failedKey = "failed_price_{$this->ticker}";

            if (\Illuminate\Support\Facades\Cache::has($fetchingKey)) {
                // Another process is already fetching this price, return 0 for now
                return 0.0;
            }

            if (\Illuminate\Support\Facades\Cache::has($failedKey)) {
                // This ticker failed recently, don't try again for a while
                return 0.0;
            }

            // Set a temporary cache to prevent other processes from fetching the same ticker
            \Illuminate\Support\Facades\Cache::put($fetchingKey, true, 30); // 30 seconds

            try {
                // If no recent price in price_assets, make API call and store it
                $priceService = app(\App\Services\PriceService::class);
                $currentPrice = $priceService->getPrice($this->ticker, $currency, $this->unit);

                if ($currentPrice !== null) {
                    // Update this position with the fresh price
                    $this->update(['price' => (string) $currentPrice]);
                    return (float) $this->quantity * $currentPrice;
                } else {
                    // Mark this ticker as failed for 1 hour to avoid repeated failed calls
                    \Illuminate\Support\Facades\Cache::put($failedKey, true, 3600); // 1 hour
                }
            } finally {
                // Always remove the fetching lock, even if an exception occurs
                \Illuminate\Support\Facades\Cache::forget($fetchingKey);
            }
        }

        // Final fallback: return 0 if no price available
        return 0.0;
    }
}
