<?php

namespace App\Models;

use App\Services\GoCardlessDataService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $gocardless_account_id
 * @property float $balance
 * @property string $end_valid_access
 * @property string $institution_id
 * @property string $agreement_id
 * @property string $reference
 * @property int $transaction_total_days
 * @property string $iban
 * @property string $currency
 * @property string $owner_name
 * @property string $cash_account_type
 * @property string $logo
 * @property string $created_at
 * @property string $updated_at
 */
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

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('user_id', function (Builder $builder) {
            $user = auth()->user();
            if ($user) {
                $builder->where('user_id', $user->id);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransactions::class);
    }

    /**
     * @return \Illuminate\Support\Collection<string, array{date: string, total: float, transactions: \Illuminate\Support\Collection<int, \App\Models\BankTransactions>}>
     */
    public function transactionsGroupedByDate(): Collection
    {
        /** @var \Illuminate\Support\Collection<int, \App\Models\BankTransactions> $transactions */
        $transactions = $this->transactions()->get();

        return $transactions
            ->groupBy(function (BankTransactions $transaction): string {
                return $transaction->transaction_date->format('Y-m-d');
            })
            ->map(function (Collection $transactions, string $date) {
                /** @var \Illuminate\Support\Collection<int, \App\Models\BankTransactions> $transactions */
                return [
                    'date' => $date,
                    'total' => (float) $transactions->sum('amount'),
                    'transactions' => $transactions,
                ];
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function updateFromGocardless(GoCardlessDataService $gocardless): array
    {
        if (! $this->gocardless_account_id) {
            return [
                'balance' => ['status' => 'error', 'message' => 'No GoCardless account ID.'],
                'transactions' => ['status' => 'error', 'message' => 'No GoCardless account ID.'],
            ];
        }
        $balanceResponse = $gocardless->updateAccountBalance($this->gocardless_account_id);
        $transactionResponse = $gocardless->updateAccountTransactions($this->gocardless_account_id);

        return [
            'balance' => $balanceResponse,
            'transactions' => $transactionResponse,
        ];
    }
}
