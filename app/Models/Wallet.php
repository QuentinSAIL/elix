<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $unit
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
}
