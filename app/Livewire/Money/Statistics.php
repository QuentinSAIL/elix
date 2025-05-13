<?php

namespace App\Livewire\Money;

use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Livewire\Component;

class Statistics extends Component
{
    public $selectedAccounts = [];
    public $availableAccounts;
    public $selectedCategories = [];
    public $availableCategories = [];
    public $dateRange = 'month';
    public $customStartDate;
    public $customEndDate;
    public $chartType = 'bar';
    public $groupBy = 'day';
    public $showIncome = true;
    public $showExpense = true;

    public function mount()
    {
        // Initialiser les dates par défaut
        $this->customStartDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->customEndDate = Carbon::now()->format('Y-m-d');

        // Charger les comptes bancaires de l'utilisateur
        $this->availableAccounts = BankAccount::all();
        if ($this->availableAccounts->count() > 0) {
            $this->selectedAccounts = [$this->availableAccounts->first()->id];
        }

        // Charger les catégories
        $this->availableCategories = MoneyCategory::all();

        // Émettre l'événement initial pour le graphique
        $this->dispatch('chart-data-updated', [
            'chartData' => $this->getChartData(),
            'categoryColors' => collect($this->getCategoryBreakdown())->pluck('color')->toArray()
        ]);
    }

    public function updatedDateRange()
    {
        // Mettre à jour les dates en fonction de la plage sélectionnée
        $now = Carbon::now();

        switch ($this->dateRange) {
            case 'today':
                $this->customStartDate = $now->format('Y-m-d');
                $this->customEndDate = $now->format('Y-m-d');
                break;
            case 'week':
                $this->customStartDate = $now->startOfWeek()->format('Y-m-d');
                $this->customEndDate = $now->format('Y-m-d');
                break;
            case 'month':
                $this->customStartDate = $now->startOfMonth()->format('Y-m-d');
                $this->customEndDate = $now->format('Y-m-d');
                break;
            case 'quarter':
                $this->customStartDate = $now->startOfQuarter()->format('Y-m-d');
                $this->customEndDate = $now->format('Y-m-d');
                break;
            case 'year':
                $this->customStartDate = $now->startOfYear()->format('Y-m-d');
                $this->customEndDate = $now->format('Y-m-d');
                break;
            case 'custom':
                // Garder les dates personnalisées telles quelles
                break;
        }
    }

    public function getTransactionsQuery()
    {
        $query = BankTransactions::query()
            ->whereIn('bank_account_id', $this->selectedAccounts);

        if (count($this->selectedCategories) > 0) {
            $query->whereIn('money_category_id', $this->selectedCategories);
        }

        $startDate = Carbon::parse($this->customStartDate)->startOfDay();
        $endDate = Carbon::parse($this->customEndDate)->endOfDay();

        $query->whereBetween('transaction_date', [$startDate, $endDate]);

        if (!$this->showIncome) {
            $query->where('amount', '<', 0);
        }

        if (!$this->showExpense) {
            $query->where('amount', '>', 0);
        }

        return $query;
    }

    public function getTransactionsData()
    {
        $transactions = $this->getTransactionsQuery()->get();

        // Grouper les transactions selon la période sélectionnée
        $groupedData = match($this->groupBy) {
            'day' => $this->groupTransactionsByDay($transactions),
            'week' => $this->groupTransactionsByWeek($transactions),
            'month' => $this->groupTransactionsByMonth($transactions),
            'category' => $this->groupTransactionsByCategory($transactions),
            'account' => $this->groupTransactionsByAccount($transactions),
            default => $this->groupTransactionsByDay($transactions),
        };

        return $groupedData;
    }

    protected function groupTransactionsByDay(Collection $transactions)
    {
        $startDate = Carbon::parse($this->customStartDate);
        $endDate = Carbon::parse($this->customEndDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $result = collect();

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayTransactions = $transactions->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });

            $income = $dayTransactions->filter(fn($t) => $t->amount > 0)->sum('amount');
            $expense = abs($dayTransactions->filter(fn($t) => $t->amount < 0)->sum('amount'));

            $result->push([
                'date' => $dateStr,
                'label' => $date->format('d/m'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
                'count' => $dayTransactions->count(),
            ]);
        }

        return $result;
    }

    protected function groupTransactionsByWeek(Collection $transactions)
    {
        $startDate = Carbon::parse($this->customStartDate)->startOfWeek();
        $endDate = Carbon::parse($this->customEndDate)->endOfWeek();

        $result = collect();
        $currentWeekStart = $startDate->copy();

        while ($currentWeekStart->lte($endDate)) {
            $weekEnd = $currentWeekStart->copy()->endOfWeek();

            $weekTransactions = $transactions->filter(function ($transaction) use ($currentWeekStart, $weekEnd) {
                return $transaction->transaction_date->between($currentWeekStart, $weekEnd);
            });

            $income = $weekTransactions->filter(fn($t) => $t->amount > 0)->sum('amount');
            $expense = abs($weekTransactions->filter(fn($t) => $t->amount < 0)->sum('amount'));

            $result->push([
                'date' => $currentWeekStart->format('Y-m-d'),
                'label' => $currentWeekStart->format('d/m') . ' - ' . $weekEnd->format('d/m'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
                'count' => $weekTransactions->count(),
            ]);

            $currentWeekStart->addWeek();
        }

        return $result;
    }

    protected function groupTransactionsByMonth(Collection $transactions)
    {
        $startDate = Carbon::parse($this->customStartDate)->startOfMonth();
        $endDate = Carbon::parse($this->customEndDate)->endOfMonth();

        $result = collect();
        $currentMonth = $startDate->copy();

        while ($currentMonth->lte($endDate)) {
            $monthEnd = $currentMonth->copy()->endOfMonth();

            $monthTransactions = $transactions->filter(function ($transaction) use ($currentMonth, $monthEnd) {
                return $transaction->transaction_date->between($currentMonth, $monthEnd);
            });

            $income = $monthTransactions->filter(fn($t) => $t->amount > 0)->sum('amount');
            $expense = abs($monthTransactions->filter(fn($t) => $t->amount < 0)->sum('amount'));

            $result->push([
                'date' => $currentMonth->format('Y-m-d'),
                'label' => $currentMonth->format('M Y'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
                'count' => $monthTransactions->count(),
            ]);

            $currentMonth->addMonth();
        }

        return $result;
    }

    protected function groupTransactionsByCategory(Collection $transactions)
    {
        $result = collect();

        $transactions = $transactions->groupBy('money_category_id');

        foreach ($transactions as $categoryId => $categoryTransactions) {
            $category = null;
            if ($categoryId && $categoryId !== '') {
                $category = MoneyCategory::find($categoryId);
            }
            $categoryName = $category ? $category->name : 'Non catégorisé';

            $income = $categoryTransactions->filter(fn($t) => $t->amount > 0)->sum('amount');
            $expense = abs($categoryTransactions->filter(fn($t) => $t->amount < 0)->sum('amount'));

            $result->push([
                'category_id' => $categoryId,
                'label' => $categoryName,
                'color' => $category ? $category->color : '#cccccc',
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
                'count' => $categoryTransactions->count(),
            ]);
        }

        return $result->sortByDesc('expense')->values();
    }

    protected function groupTransactionsByAccount(Collection $transactions)
    {
        $result = collect();

        $transactions = $transactions->groupBy('bank_account_id');

        foreach ($transactions as $accountId => $accountTransactions) {
            $account = BankAccount::find($accountId);
            $accountName = $account ? $account->name : 'Compte inconnu';

            $income = $accountTransactions->filter(fn($t) => $t->amount > 0)->sum('amount');
            $expense = abs($accountTransactions->filter(fn($t) => $t->amount < 0)->sum('amount'));

            $result->push([
                'account_id' => $accountId,
                'label' => $accountName,
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
                'count' => $accountTransactions->count(),
            ]);
        }

        return $result;
    }

    public function getChartData()
    {
        $data = $this->getTransactionsData();

        $chartData = [
            'labels' => $data->pluck('label')->toArray(),
            'datasets' => []
        ];

        if ($this->showIncome) {
            $chartData['datasets'][] = [
                'label' => 'Revenus',
                'data' => $data->pluck('income')->toArray(),
                'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 1,
            ];
        }

        if ($this->showExpense) {
            $chartData['datasets'][] = [
                'label' => 'Dépenses',
                'data' => $data->pluck('expense')->toArray(),
                'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1,
            ];
        }

        return $chartData;
    }

    public function getTotalIncome()
    {
        return $this->getTransactionsQuery()
            ->where('amount', '>', 0)
            ->sum('amount');
    }

    public function getTotalExpense()
    {
        return abs($this->getTransactionsQuery()
            ->where('amount', '<', 0)
            ->sum('amount'));
    }

    public function getNetAmount()
    {
        return $this->getTotalIncome() - $this->getTotalExpense();
    }

    public function getCategoryBreakdown()
    {
        return $this->groupTransactionsByCategory($this->getTransactionsQuery()->get());
    }

    public function updated($property)
    {
        // Émettre un événement pour mettre à jour le graphique
        $this->dispatch('chart-data-updated', [
            'chartData' => $this->getChartData(),
            'categoryColors' => collect($this->getCategoryBreakdown())->pluck('color')->toArray()
        ]);
    }

    public function render()
    {
        return view('livewire.money.statistics', [
            'chartData' => $this->getChartData(),
            'totalIncome' => $this->getTotalIncome(),
            'totalExpense' => $this->getTotalExpense(),
            'netAmount' => $this->getNetAmount(),
            'categoryBreakdown' => $this->getCategoryBreakdown(),
        ]);
    }
}
