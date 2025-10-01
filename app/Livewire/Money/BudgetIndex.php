<?php

namespace App\Livewire\Money;

use App\Models\MoneyCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BudgetIndex extends Component
{
    public $user;

    public string $month;

    public string $monthLabel;

    /** @var array<int, array{category: MoneyCategory, budget: float|null, spent: float, remaining: float|null, overspent: bool}> */
    public array $rows = [];

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->month = now()->format('Y-m');
        $this->updateMonthLabel();
        $this->loadRows();
    }

    public function updatedMonth(): void
    {
        $this->updateMonthLabel();
        $this->loadRows();
    }

    public function prevMonth(): void
    {
        $date = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->subMonth();
        $this->month = $date->format('Y-m');
        $this->updateMonthLabel();
        $this->loadRows();
    }

    public function nextMonth(): void
    {
        $date = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->addMonth();
        $this->month = $date->format('Y-m');
        $this->updateMonthLabel();
        $this->loadRows();
    }

    public function goToCurrentMonth(): void
    {
        $this->month = now()->format('Y-m');
        $this->updateMonthLabel();
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $month = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $categories = $this->user->moneyCategories()->orderBy('name')->get();

        $this->rows = [];
        foreach ($categories as $category) {
            /** @var MoneyCategory $category */
            $spent = $category->spentForMonth($month);
            $remaining = $category->remainingForMonth($month);
            $this->rows[] = [
                'category' => $category,
                'budget' => $category->budget !== null ? (float) $category->budget : null,
                'spent' => (float) $spent,
                'remaining' => $remaining,
                'overspent' => $category->isOverspentForMonth($month),
            ];
        }
    }

    private function updateMonthLabel(): void
    {
        $this->monthLabel = Carbon::createFromFormat('Y-m', $this->month)
            ->startOfMonth()
            ->translatedFormat('F Y');
    }

    public function render()
    {
        return view('livewire.money.budget-index');
    }
}
