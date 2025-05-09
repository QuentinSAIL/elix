<?php

namespace App\Models;

use App\Models\User;
use App\Models\BankTransactions;
use Illuminate\Support\Collection;
use App\Services\GoCardlessDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'created_at',
        'updated_at'
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

    public function transactionsGroupedByDate()
    {
        return $this->transactions()
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->transaction_date->format('Y-m-d');
            })
            ->map(function ($transactions, $date) {
                return [
                    'date' => $date,
                    'total' => $transactions->sum('amount'),
                    'transactions' => $transactions,
                ];
            });
    }

    public function updateFromGocardless(GoCardlessDataService $gocardless)
    {
        $balanceResponse = $gocardless->updateAccountBalance($this->gocardless_account_id);
        $transactionResponse = $gocardless->updateAccountTransactions($this->gocardless_account_id);
        return [
            'balance' => $balanceResponse,
            'transactions' => $transactionResponse,
        ];
    }
}
