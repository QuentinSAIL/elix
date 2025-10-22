<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\MoneyCategory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TransactionCacheService
{
    private const CACHE_DURATION = 300; // 5 minutes

    private const USER_CACHE_PREFIX = 'user_transactions_';

    private const ACCOUNT_CACHE_PREFIX = 'account_transactions_';

    private const CATEGORY_CACHE_PREFIX = 'categories_';

    /**
     * Get cached transaction counts for user accounts
     */
    public function getUserAccountCounts(User $user): array
    {
        $cacheKey = self::USER_CACHE_PREFIX.$user->id.'_counts';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            return $user->bankAccounts()->withCount('transactions')->get()
                ->pluck('transactions_count', 'id')
                ->toArray();
        });
    }

    /**
     * Get cached total transaction count for user
     */
    public function getUserTotalCount(User $user): int
    {
        $cacheKey = self::USER_CACHE_PREFIX.$user->id.'_total';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            return $user->bankTransactions()->count();
        });
    }

    /**
     * Get cached categories for a specific user
     */
    public function getCategories(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CATEGORY_CACHE_PREFIX.$user->id;

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            return $user->moneyCategories()->orderBy('name')->get();
        });
    }

    /**
     * Clear cache for a specific user
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget(self::USER_CACHE_PREFIX.$user->id.'_counts');
        Cache::forget(self::USER_CACHE_PREFIX.$user->id.'_total');
        Cache::forget(self::CATEGORY_CACHE_PREFIX.$user->id);
    }

    /**
     * Clear cache for a specific account
     */
    public function clearAccountCache(BankAccount $account): void
    {
        Cache::forget(self::ACCOUNT_CACHE_PREFIX.$account->id);
        /** @var \App\Models\User $user */
        $user = $account->user;
        $this->clearUserCache($user);
    }

    /**
     * Clear all transaction caches
     */
    public function clearAllCaches(): void
    {
        Cache::flush();
    }

    /**
     * Get optimized transaction query with caching
     */
    public function getOptimizedTransactionQuery(User $user, ?BankAccount $selectedAccount = null, array $filters = []): \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        $query = $selectedAccount
            ? $selectedAccount->transactions()
            : $user->bankTransactions();

        // Apply filters
        if (! empty($filters['search'])) {
            $query->whereRaw('LOWER(description) LIKE ?', ['%'.strtolower($filters['search']).'%']);
        }

        if (! empty($filters['category'])) {
            $query->where('money_category_id', $filters['category']);
        }

        if (! empty($filters['date_range'])) {
            $query->whereBetween('transaction_date', $filters['date_range']);
        }

        return $query->with(['category', 'account']);
    }

    /**
     * Warm up cache for user
     */
    public function warmUpUserCache(User $user): void
    {
        $this->getUserAccountCounts($user);
        $this->getUserTotalCount($user);
        $this->getCategories($user);
    }
}
