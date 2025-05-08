<?php

namespace App\Models;

use App\Models\MoneyCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoneyCategoryMatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
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

    public static function checkAndApplyCategory($transaction)
    {
        $category = self::where('keyword', $transaction->description)->first()?->category;
        if ($category) {
            $transaction->money_category_id = $category->id;
            $transaction->save();
        }
    }

    // searc in the database in bank_transaction.description if a matching keyword exist
    // and is not already assigned to a category
    public static function searchAndApplyCategory()
    {
        $transactions = Auth::user()->bankTransactions()->get();
        $i = 0;
        foreach ($transactions as $transaction) {
            $category = self::where('keyword', $transaction->description)->first()?->category;
            if ($category) {
                $transaction->money_category_id = $category->id;
                $transaction->save();
                $i++;
            }
        }

        return $i;
    }
}
