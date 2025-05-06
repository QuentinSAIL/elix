<?php

namespace App\Models;

use App\Models\BankAccount;
use App\Models\MoneyCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransactions extends Model
{
    protected $fillable = [
        'bank_account_id',
        'category_id',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MoneyCategory::class);
    }
}
