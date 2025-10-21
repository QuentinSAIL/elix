<?php

namespace App\Models;

use App\Services\WalletUpdateService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property int $id
 * @property string $description
 * @property string $money_category_id
 * @property float $amount
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property-read \App\Models\MoneyCategory $category
 * @property-read \App\Models\BankAccount $account
 *
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo account()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo category()
 */
class BankTransactions extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'bank_account_id',
        'money_category_id',
        'gocardless_transaction_id',
        'amount',
        'original_description', // a stocker seulement quand l'user modifie la description
        'description',
        'transaction_date', // booking date in json
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        // Update wallet when transaction category is set or changed
        static::saved(function (BankTransactions $transaction) {
            if ($transaction->wasChanged('money_category_id')) {
                app(WalletUpdateService::class)->updateWalletFromTransaction($transaction);
            }
        });
    }

    public function account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function category()
    {
        return $this->belongsTo(MoneyCategory::class, 'money_category_id');
    }
}
