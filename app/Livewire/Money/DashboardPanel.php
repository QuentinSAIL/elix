<?php

namespace App\Livewire\Money;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\MoneyCategory;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;

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
        $this->title = $this->panel?->title ?? 'Dashboard Panel';
        $this->isExpensePanel = $this->panel?->is_expense ?? true; // Set isExpensePanel based on panel property

        $this->categories = $this->panel->categories()->get()->pluck('id')->toArray();
        $this->bankAccounts = $this->panel->bankAccounts()->get()->pluck('id')->toArray();
        $this->assignDateRange();
        $this->transactions = $this->getTransactions();
        $this->prepareChartData();
    }

    public function prepareChartData()
    {
        // Filter transactions based on panel type (expenses or income)
        $filteredTransactions = $this->transactions->filter(function ($transaction) {
            // First filter by expense/income type
            $amountCondition = $this->isExpensePanel
            ? (float) $transaction->amount < 0 // Only negative values for expenses
            : (float) $transaction->amount > 0; // Only positive values for income

            // Then exclude uncategorized transactions if displayUncategorized is false
            if (!$this->displayUncategorized && !$transaction->category) {
            return false;
            }

            return $amountCondition;
        });

        // Use safe fallback for category names
        $this->labels = $filteredTransactions
            ->map(function ($transaction) {
            return $transaction->category ? $transaction->category->name : 'Uncategorized';
            })
            ->unique()
            ->values()
            ->toArray();

        // Group by category name with fallback using filtered transactions
        $this->values = $filteredTransactions
            ->groupBy(function ($transaction) {
            return $transaction->category ? $transaction->category->name : 'Uncategorized';
            })
            ->map(function ($group) {
            return $group->sum('amount');
            })
            ->values()
            ->toArray();

        // Get colors for categories
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
        Toaster::info('Editer');
    }

    public function render()
    {
        return view('livewire.money.dashboard-panel');
    }
}
