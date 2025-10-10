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
     * Update the current market price for this position
     */
    public function updateCurrentPrice(): bool
    {
        if (! $this->ticker) {
            return false;
        }

        $priceService = app(\App\Services\PriceService::class);
        $currentPrice = $priceService->getPrice($this->ticker, $this->wallet->unit);

        if ($currentPrice !== null) {
            $this->update(['price' => (string) $currentPrice]);

            return true;
        }

        return false;
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
}
