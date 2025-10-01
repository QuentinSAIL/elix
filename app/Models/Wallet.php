<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $unit
 * @property string $mode
 * @property string $balance
 * @property string $category_linked_id
 * @property string $created_at
 * @property string $updated_at
 */
class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'unit',
        'mode',
        'balance',
        'category_linked_id',
        'created_at',
        'updated_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'balance' => 'decimal:18',
        'mode' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('user_id', function (Builder $builder) {
            $userId = Auth::id();
            if ($userId) {
                $builder->where('user_id', $userId);
            }
        });

        static::creating(function (Wallet $wallet): void {
            // Ensure unit default
            if (! $wallet->unit) {
                $wallet->unit = 'EUR';
            }

            // Ensure mode default
            if (! $wallet->mode) {
                $wallet->mode = 'single';
            }
        });

        static::created(function (Wallet $wallet): void {
            // Auto-create a category if none is linked
            if (! $wallet->category_linked_id) {
                /** @var \App\Models\MoneyCategory $category */
                $category = MoneyCategory::create([
                    'user_id' => $wallet->user_id,
                    'name' => 'transfert vers '.$wallet->name,
                    'description' => 'Category linked to wallet '.$wallet->name,
                ]);
                $wallet->category_linked_id = (string) $category->id;
                $wallet->save();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MoneyCategory::class, 'category_linked_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(WalletPosition::class);
    }

    /**
     * Check if wallet is in single mode
     */
    public function isSingleMode(): bool
    {
        return $this->mode === 'single';
    }

    /**
     * Check if wallet is in multi mode
     */
    public function isMultiMode(): bool
    {
        return $this->mode === 'multi';
    }

    /**
     * Get the current balance based on wallet mode
     */
    public function getCurrentBalance(): float
    {
        if ($this->isSingleMode()) {
            // In single mode, return the stored balance
            return (float) $this->balance;
        }

        // In multi mode, calculate balance from positions with current prices
        return $this->calculateBalanceFromPositions();
    }

    /**
     * Calculate balance from positions using current market prices
     */
    public function calculateBalanceFromPositions(): float
    {
        $positions = $this->positions()->get();

        if ($positions->isEmpty()) {
            return (float) $this->balance;
        }

        $totalValue = 0;
        foreach ($positions as $position) {
            // Get current price (will update stored price if ticker exists)
            $currentPrice = $position->getCurrentPrice();
            $totalValue += (float) $position->quantity * $currentPrice;
        }

        return $totalValue;
    }

    /**
     * Update balance for single mode wallets
     */
    public function updateBalance(float $newBalance): void
    {
        if ($this->isSingleMode()) {
            $this->update(['balance' => (string) $newBalance]);
        }
    }
}
