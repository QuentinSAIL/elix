<?php

namespace App\Models;

use App\Models\MoneyCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoneyCategoryMatch extends Model
{
    protected $fillable = [
        'category_id',
        'keyword',
        'created_at',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('user_id', function (Builder $builder) {
            $builder->whereHas('category', function (Builder $query) {
                $query->where('user_id', auth()->id());
            });
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MoneyCategory::class);
    }
}
