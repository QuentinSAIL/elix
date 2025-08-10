<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MoneyCategoryMatch> $categoryMatches
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BankTransactions> $transactions
 * @method static \Database\Factories\MoneyCategoryFactory factory($count = null, $state = [])
 */
class MoneyCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'description',
        'color',
        'budget',
        'include_in_dashboard',
        'user_id',
        'created_at',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('created_at', function (Builder $builder) {
            $builder->orderBy('created_at', 'desc');
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransactions::class);
    }

    public function categoryMatches(): HasMany
    {
        return $this->hasMany(MoneyCategoryMatch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
