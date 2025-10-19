<?php

namespace App\Livewire\Money;

use App\Models\MoneyCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPanel extends Component
{
    public $user;

    public $title;

    public $displayUncategorized = false;

    public $panel;

    public $bankAccounts;

    public $categories;

    public $transactions;

    public $startDate;

    public $endDate;

    public $labels = [];

    public $values = [];

    public $colors = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->title = $this->panel->title ?? __('Dashboard');
        if ($this->panel && in_array($this->panel->period_type, ['actual_month', 'previous_month', 'two_months_ago', 'three_months_ago'])) {
            $periode = $this->panel->determinePeriode();
            if (isset($periode['startDate'])) {
                $this->title .= ' ('.$periode['startDate']->translatedFormat('F').')';
            }
        }

        $this->categories = $this->panel->categories ? $this->panel->categories()->get()->pluck('id')->toArray() : [];
        $this->bankAccounts = $this->panel->bankAccounts ? $this->panel->bankAccounts()->get()->pluck('id')->toArray() : [];
        $this->assignDateRange();
        $this->transactions = $this->getTransactions();
        $this->prepareChartData();
    }

    public function prepareChartData()
    {
        // Reset colors array
        $this->colors = [];

        $filteredTransactions = $this->transactions->filter(function ($transaction) {
            if (! $this->displayUncategorized && ! $transaction->category) {
                return false;
            }

            return true;
        });

        // For number type, we just need the total sum
        if ($this->panel->type === 'number') {
            $this->values = [$filteredTransactions->sum('amount')];
            $this->labels = ['Total'];
            $this->colors = ['#3B82F6']; // Blue color for total

            return;
        }

        // For gauge type, we need positive and negative totals
        if ($this->panel->type === 'gauge') {
            $positiveTotal = $filteredTransactions->where('amount', '>', 0)->sum('amount');
            $negativeTotal = abs($filteredTransactions->where('amount', '<', 0)->sum('amount'));
            $this->values = [$positiveTotal, $negativeTotal];
            $this->labels = ['Revenus', 'Dépenses'];
            $this->colors = ['#10B981', '#EF4444']; // Green for income, red for expenses

            return;
        }

        // For trend type, we need daily data
        if ($this->panel->type === 'trend') {
            $dailyData = $filteredTransactions
                ->groupBy(function ($transaction) {
                    return \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d');
                })
                ->map(function ($group) {
                    return $group->sum('amount');
                })
                ->sortKeys();

            $this->labels = $dailyData->keys()->toArray();
            $this->values = $dailyData->values()->toArray();
            $this->colors = ['#8B5CF6']; // Purple for trend

            return;
        }

        // For category comparison type
        if ($this->panel->type === 'category_comparison') {
            $categoryData = $filteredTransactions
                ->groupBy(function ($transaction) {
                    return $transaction->category ? $transaction->category->name : 'Uncategorized';
                })
                ->map(function ($group) {
                    return $group->sum('amount');
                })
                ->sortByDesc(function ($amount) {
                    return abs($amount);
                });

            $this->labels = $categoryData->keys()->toArray();
            $this->values = $categoryData->values()->toArray();

            foreach ($this->labels as $label) {
                $category = MoneyCategory::where('name', $label)->first();
                $this->colors[] = $category ? $category->color : '#CCCCCC';
            }

            return;
        }

        // Default behavior for other chart types (bar, pie, doughnut, line, table)
        $this->labels = $filteredTransactions
            ->map(function ($transaction) {
                return $transaction->category ? $transaction->category->name : 'Uncategorized';
            })
            ->unique()
            ->values()
            ->toArray();

        $this->values = $filteredTransactions
            ->groupBy(function ($transaction) {
                return $transaction->category ? $transaction->category->name : 'Uncategorized';
            })
            ->map(function ($group) {
                return $group->sum('amount');
            })
            ->values()
            ->toArray();

        foreach ($this->labels as $label) {
            $category = MoneyCategory::where('name', $label)->first();
            $this->colors[] = $category ? $category->color : '#CCCCCC';
        }
    }

    public function assignDateRange()
    {
        $period = $this->panel->determinePeriode();
        $this->startDate = $period['startDate'] ? $period['startDate']->format('Y-m-d') : null;
        $this->endDate = $period['endDate'] ? $period['endDate']->format('Y-m-d') : null;
    }

    public function getTransactions()
    {
        return $this->panel->getTransactions($this->startDate, $this->endDate, [
            'accounts' => $this->bankAccounts,
            'categories' => $this->categories,
        ]);
    }

    public function updatedDisplayUncategorized()
    {
        $this->prepareChartData();
    }

    public function edit()
    {
        $this->dispatch('edit-panel', $this->panel->id);
    }

    /**
     * Format amount intelligently - remove cents for amounts over 100€
     */
    public function formatAmount($amount)
    {
        if (abs($amount) >= 100) {
            return number_format($amount, 0);
        }

        return number_format($amount, 2);
    }

    public function render()
    {
        return view('livewire.money.dashboard-panel');
    }
}
