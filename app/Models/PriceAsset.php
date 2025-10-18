<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $ticker
 * @property string $type
 * @property float|null $price
 * @property string $currency
 * @property \Carbon\Carbon|null $last_updated
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PriceAsset extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'ticker',
        'type',
        'price',
        'currency',
        'last_updated',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'price' => 'decimal:18',
        'last_updated' => 'datetime',
    ];

    /**
     * Types d'actifs disponibles
     */
    public const TYPES = [
        'CRYPTO' => 'Cryptocurrency',
        'TOKEN' => 'Token',
        'STOCK' => 'Stock',
        'COMMODITY' => 'Commodity',
        'ETF' => 'ETF',
        'BOND' => 'Bond',
        'OTHER' => 'Other',
    ];

    /**
     * Trouver ou créer un actif par ticker et type
     */
    public static function findOrCreate(string $ticker, string $type = 'OTHER'): self
    {
        return static::firstOrCreate(
            ['ticker' => strtoupper($ticker)],
            [
                'type' => $type,
                'currency' => 'EUR',
                'last_updated' => null,
            ]
        );
    }

    /**
     * Mettre à jour le prix et la date de dernière mise à jour
     */
    public function updatePrice(?float $price, string $currency = 'EUR'): bool
    {
        return $this->update([
            'price' => $price,
            'currency' => $currency,
            'last_updated' => now(),
        ]);
    }

    /**
     * Vérifier si le prix est récent (moins de 12 heures)
     */
    public function isPriceRecent(): bool
    {
        if (!$this->last_updated) {
            return false;
        }

        return $this->last_updated->isAfter(now()->subHours(12));
    }

    /**
     * Obtenir le prix actuel ou null si pas disponible
     */
    public function getCurrentPrice(): ?float
    {
        return $this->price;
    }

    /**
     * Scope pour les actifs avec prix récent
     */
    public function scopeWithRecentPrice($query)
    {
        return $query->whereNotNull('price')
            ->where('last_updated', '>=', now()->subHours(12));
    }

    /**
     * Scope pour les actifs nécessitant une mise à jour
     */
    public function scopeNeedsUpdate($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_updated')
                ->orWhere('last_updated', '<', now()->subHours(12));
        });
    }
}
