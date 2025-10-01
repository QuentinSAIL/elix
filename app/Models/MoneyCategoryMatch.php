<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $id
 * @property string $user_id
 * @property string $money_category_id
 * @property string $keyword
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MoneyCategory $category
 * @property-read \App\Models\User $user
 */
class MoneyCategoryMatch extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'money_category_id', 'keyword', 'created_at', 'updated_at'];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('user_id', function (Builder $builder) {
            $builder->whereHas('category', function (Builder $query) {
                $query->where('user_id', auth()->id());
            });
        });
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MoneyCategory::class, 'money_category_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function checkAndApplyCategory(\App\Models\BankTransactions $transaction): void
    {
        $match = MoneyCategoryMatch::whereRaw('? LIKE \'%\' || LOWER(keyword) || \'%\'', [strtolower($transaction->description)])->first();
        if ($match) {
            $transaction->money_category_id = (string) $match->category->id;
            $transaction->save();
            // The wallet update will be triggered automatically by the BankTransactions model boot method
        }
    }

    public static function searchAndApplyAllMatchCategory(): int
    {
        $transactions = Auth::user()->bankTransactions()->get();
        $i = 0;
        foreach ($transactions as $transaction) {
            /** @var \App\Models\BankTransactions $transaction */
            $match = MoneyCategoryMatch::whereRaw('? LIKE \'%\' || LOWER(keyword) || \'%\'', [strtolower($transaction->description)])->first();
            if ($match && $transaction->money_category_id !== (string) $match->category->id) {
                $transaction->money_category_id = (string) $match->category->id;
                $transaction->save();
                $i++;
            }
        }

        return $i;
    }

    public static function searchAndApplyMatchCategory(string $keyword, bool $applyMatchToAlreadyCategorized = false): int
    {
        $match = MoneyCategoryMatch::whereRaw('? LIKE \'%\' || LOWER(keyword) || \'%\'', [strtolower($keyword)])->first();
        $i = 0;
        if ($match) {
            $transactions = Auth::user()->bankTransactions()->where('description', 'LIKE', '%'.$keyword.'%')->get();
            foreach ($transactions as $transaction) {
                /** @var \App\Models\BankTransactions $transaction */
                if ($applyMatchToAlreadyCategorized || ! $transaction->money_category_id) {
                    $transaction->money_category_id = (string) $match->category->id;
                    $transaction->save();
                    $i++;
                }
            }
        }

        return $i;
    }
}
