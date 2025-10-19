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
     * Update the current market price for this position in price_assets table
     * No longer updates the price in wallet_positions table
     */
    public function updateCurrentPrice(): bool
    {
        if (! $this->ticker) {
            return false;
        }

        $priceService = app(\App\Services\PriceService::class);
        $currentPrice = $priceService->getPrice($this->ticker, $this->wallet->unit, $this->unit);

        if ($currentPrice !== null) {
            // Update price in price_assets table instead of wallet_positions
            $priceAsset = \App\Models\PriceAsset::findOrCreate($this->ticker, 'OTHER');
            $priceAsset->updatePrice($currentPrice, $this->wallet->unit);

            return true;
        }

        return false;
    }


    /**
     * Get the current market price from price_assets table (if ticker exists) or stored price
     */
    public function getCurrentPrice(): float
    {
        // If we have a ticker, ONLY use price_assets table, never wallet_positions
        if ($this->ticker) {
            $priceAsset = \App\Models\PriceAsset::where('ticker', $this->ticker)->first();

            if ($priceAsset && $priceAsset->price !== null && $priceAsset->isPriceRecent()) {
                return (float) $priceAsset->price;
            }

            // If no recent price in price_assets, try to get fresh price via PriceService
            $priceService = app(\App\Services\PriceService::class);
            $currentPrice = $priceService->getPrice($this->ticker, $this->wallet->unit, $this->unit);

            if ($currentPrice !== null) {
                // Create or update PriceAsset with the fresh price
                $priceAsset = \App\Models\PriceAsset::findOrCreate($this->ticker, 'OTHER');
                $priceAsset->updatePrice($currentPrice, $this->wallet->unit);
                return $currentPrice;
            }

            // If no price available from APIs, return 0 instead of wallet_positions price
            return 0.0;
        }

        // Fallback to stored price only if no ticker
        return (float) $this->price;
    }

    /**
     * Calculate the total value of this position using current price
     */
    public function getValue(): float
    {
        return (float) $this->quantity * $this->getCurrentPrice();
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
     * Always uses price_assets table if ticker exists, otherwise falls back to stored price
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

        // If we have a ticker, ONLY use price_assets table, never wallet_positions
        if ($this->ticker) {
            $priceAsset = \App\Models\PriceAsset::where('ticker', $this->ticker)->first();

            if ($priceAsset && $priceAsset->price !== null && $priceAsset->isPriceRecent()) {
                // Use price from price_assets table only if it's recent
                return (float) $this->quantity * (float) $priceAsset->price;
            }

            // If no recent price in price_assets, try to get fresh price via PriceService
            $priceService = app(\App\Services\PriceService::class);
            $currentPrice = $priceService->getPrice($this->ticker, $currency, $this->unit);

            if ($currentPrice !== null) {
                // Create or update PriceAsset with the fresh price
                $priceAsset = \App\Models\PriceAsset::findOrCreate($this->ticker, 'OTHER');
                $priceAsset->updatePrice($currentPrice, $currency);
                return (float) $this->quantity * $currentPrice;
            }

            // If no price available from APIs, return 0 instead of wallet_positions price
            return 0.0;
        }

        // Fallback to stored price only if no ticker
        if ($this->price && (float) $this->price > 0) {
            return (float) $this->quantity * (float) $this->price;
        }

        // Final fallback: return 0 if no price available
        return 0.0;
    }
}
