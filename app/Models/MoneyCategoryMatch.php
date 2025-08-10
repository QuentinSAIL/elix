<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MoneyCategoryMatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id', 'user_id', 'money_category_id', 'keyword', 'created_at', 'updated_at'];

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
        $match = MoneyCategoryMatch::whereRaw('? LIKE \'%\' || LOWER(keyword) || \'%\'', [strtolower($transaction->description)])->first();
        if ($match) {
            $transaction->money_category_id = $match->category->id;
            $transaction->save();
        }
    }

    public static function searchAndApplyAllMatchCategory()
    {
        $transactions = Auth::user()->bankTransactions()->get();
        $i = 0;
        foreach ($transactions as $transaction) {
            $match = MoneyCategoryMatch::whereRaw('? LIKE \'%\' || LOWER(keyword) || \'%\'', [strtolower($transaction->description)])->first();
            if ($match && $transaction->money_category_id !== $match->category->id) {
                $transaction->money_category_id = $match->category->id;
                $transaction->save();
                $i++;
            }
        }

        return $i;
    }

    public static function searchAndApplyMatchCategory($keyword, $applyMatchToAlreadyCategorized = false)
    {
        $match = MoneyCategoryMatch::whereRaw('? LIKE \'%\' || LOWER(keyword) || \'%\'', [strtolower($keyword)])->first();
        $i = 0;
        if ($match) {
            $transactions = Auth::user()->bankTransactions()->where('description', 'LIKE', '%'.$keyword.'%')->get();
            foreach ($transactions as $transaction) {
                if ($applyMatchToAlreadyCategorized || ! $transaction->money_category_id) {
                    $transaction->money_category_id = $match->category->id;
                    $transaction->save();
                    $i++;
                }
            }
        }

        return $i;
    }
}
