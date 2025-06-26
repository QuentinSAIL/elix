<?php

namespace App\Models;

use App\Services\GoCardlessDataService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'gocardless_account_id',
        'balance',
        'end_valid_access',
        'institution_id',
        'agreement_id',
        'reference',
        'transaction_total_days',
        'iban',
        'currency',
        'owner_name',
        'cash_account_type',
        'logo',
        'created_at',
        'updated_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('user_id', function (Builder $builder) {
            $builder->where('user_id', auth()->id());
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransactions::class);
    }

    

    public function updateFromGocardless(GoCardlessDataService $gocardless)
    {
        if (! $this->gocardless_account_id) {
            return;
        }
        $balanceResponse = $gocardless->updateAccountBalance($this->gocardless_account_id);
        $transactionResponse = $gocardless->updateAccountTransactions($this->gocardless_account_id);

        return [
            'balance' => $balanceResponse,
            'transactions' => $transactionResponse,
        ];
    }
}
