<?php

namespace App\Models;

use App\Models\BankAccount;
use App\Models\MoneyCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankTransactions extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'bank_account_id',
        'money_category_id',
        'gocardless_transaction_id',
        'amount',
        'original_description', // a stocker seulement quand je modifie description
        'description',
        'transaction_date', // booking date in json
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function category()
    {
        return $this->belongsTo(MoneyCategory::class, 'money_category_id');
    }
}
