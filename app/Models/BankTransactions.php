<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function category()
    {
        return $this->belongsTo(MoneyCategory::class, 'money_category_id');
    }
}
