<?php

namespace App\Livewire\Money;

use App\Models\MoneyCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPanel extends Component
{
    public $user;

    public $title;

    public $isExpensePanel = true;

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
        if (in_array($this->panel->period_type, ['actual_month', 'previous_month', 'two_months_ago', 'three_months_ago'])) {
            $this->title .= ' ('.($this->panel->determinePeriode()['startDate']->translatedFormat('F') ?? '').')';
        }

        $this->isExpensePanel = $this->panel->is_expense ?? true;

        $this->categories = $this->panel->categories()->get()->pluck('id')->toArray();
        $this->bankAccounts = $this->panel->bankAccounts()->get()->pluck('id')->toArray();
        $this->assignDateRange();
        $this->transactions = $this->getTransactions();
        $this->prepareChartData();
    }

    public function prepareChartData()
    {
        $filteredTransactions = $this->transactions->filter(function ($transaction) {
            $amountCondition = $this->isExpensePanel
            ? (float) $transaction->amount < 0 // Only negative values for expenses
            : (float) $transaction->amount > 0; // Only positive values for income

            if (! $this->displayUncategorized && ! $transaction->category) {
                return false;
            }

            return $amountCondition;
        });

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
            $this->colors[] = $category ? $category->color : '#CCCCCC'; // Default color for uncategorized
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

    public function edit()
    {
        $this->dispatch('edit-panel', $this->panel->id);
    }

    public function render()
    {
        return view('livewire.money.dashboard-panel');
    }
}
