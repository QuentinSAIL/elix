<?php

namespace App\Models;

use App\Models\MoneyCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoneyCategoryMatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'money_category_id',
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

    public function category()
    {
        return $this->belongsTo(MoneyCategory::class, 'money_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applyCategory($transaction, $category)
    {
        $transaction->money_category_id = $category->id;
        $transaction->save();
    }

    public function applyCategoryToEveryMatchingTransaction($transaction, $category)
    {
        $matchingTransactions = $transaction->where('description', 'like', '%' . $this->keyword . '%')->get();
        foreach ($matchingTransactions as $matchingTransaction) {
            $this->applyCategory($matchingTransaction, $category);
        }
    }
}
