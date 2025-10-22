<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $color
 * @property float|null $budget
 * @property bool $include_in_dashboard
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MoneyCategoryMatch> $categoryMatches
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BankTransactions> $transactions
 * @property-read \App\Models\Wallet|null $wallet
 *
 * @method static \Database\Factories\MoneyCategoryFactory factory($count = null, $state = [])
 */
class MoneyCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'description',
        'color',
        'budget',
        'include_in_dashboard',
        'user_id',
        'created_at',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('user_id', Auth::id());
            }
        });

        static::addGlobalScope('created_at', function (Builder $builder) {
            $builder->orderBy('created_at', 'desc');
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransactions::class);
    }

    public function categoryMatches(): HasMany
    {
        return $this->hasMany(MoneyCategoryMatch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'category_linked_id');
    }

    /**
     * Sum of expenses (negative amounts) for this category within a month.
     */
    public function spentForMonth(Carbon $month): float
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        /** @var float $sum */
        $sum = $this->transactions()
            ->whereBetween('transaction_date', [$start, $end])
            ->where('amount', '<', 0)
            ->sum('amount');

        return (float) $sum; // negative total for expenses
    }

    /**
     * Remaining budget for given month. Returns null if no budget set.
     * Note: amounts are negative for expenses, so remaining = budget + spent (spent is negative).
     */
    public function remainingForMonth(Carbon $month): ?float
    {
        if ($this->budget === null) {
            return null;
        }

        $spent = $this->spentForMonth($month); // negative

        return (float) $this->budget + (float) $spent;
    }

    /**
     * True if category is overspent for given month.
     */
    public function isOverspentForMonth(Carbon $month): bool
    {
        $remaining = $this->remainingForMonth($month);

        return $remaining !== null && $remaining < 0;
    }
}
