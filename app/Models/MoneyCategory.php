<?php

namespace App\Models;

use App\Models\User;
use App\Models\BankTransactions;
use App\Models\MoneyCategoryMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoneyCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'description',
        'color',
        'user_id',
        'created_at',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('user_id', function (Builder $builder) {
            $builder->where('user_id', auth()->id());
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
