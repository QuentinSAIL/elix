<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TransactionService
{
    public function getTransactions($selectedAccount, $allAccounts, $search, $categoryFilter, $dateFilter, $sortField, $sortDirection, $perPage)
    {
        $query = $this->getTransactionQuery($selectedAccount, $allAccounts, $search, $categoryFilter, $dateFilter);

        if ($query instanceof \Illuminate\Database\Eloquent\Collection) {
            return collect();
        }

        return $query
            ->orderBy($sortField, $sortDirection)
            ->take($perPage)
            ->get();
    }

    protected function getTransactionQuery($selectedAccount, $allAccounts, $search, $categoryFilter, $dateFilter)
    {
        $user = Auth::user();

        if (! $selectedAccount && ! $allAccounts) {
            return collect();
        }

        if ($allAccounts) {
            $query = $user->bankTransactions();
        } else {
            $query = $selectedAccount->transactions();
        }

        if (Str::length($search) > 0) {
            $query->whereRaw('LOWER(description) LIKE ?', ['%'.strtolower($search).'%']);
        }

        if ($categoryFilter) {
            $query->where('money_category_id', $categoryFilter);
        }

        switch ($dateFilter) {
            case 'current_month':
                $query->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year);
                break;
            case 'last_month':
                $query->whereMonth('transaction_date', now()->subMonth()->month)
                    ->whereYear('transaction_date', now()->subMonth()->year);
                break;
            case 'current_year':
                $query->whereYear('transaction_date', now()->year);
                break;
        }

        return $query;
    }

    public function getGroupedTransactions($account)
    {
        return $account->transactions()
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
}
