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
        $currentPrice = $priceService->getPrice($this->ticker, $this->wallet->unit);

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
            $currentPrice = $priceService->getPrice($this->ticker, $this->wallet->unit);

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
     * Get current market value for this position in user's preferred currency
     */
    public function getCurrentMarketValue(string $userCurrency = null): float
    {
        if ($this->ticker) {
            $priceService = app(\App\Services\PriceService::class);

            // Use provided currency or wallet currency or EUR as fallback
            $currency = $userCurrency ?? ($this->wallet ? $this->wallet->unit : 'EUR');

            // Try to get price directly in the target currency first
            $currentPrice = $priceService->getPrice($this->ticker, $currency);

            if ($currentPrice !== null) {
                return (float) $this->quantity * $currentPrice;
            }

            // If direct price not available, try conversion from USD
            $currentPrice = $priceService->getPriceInCurrency($this->ticker, $currency, 'USD');
            if ($currentPrice !== null) {
                return (float) $this->quantity * $currentPrice;
            }
        }

        // Fallback to stored price
        return (float) $this->quantity * (float) $this->price;
    }
}
