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

    public string $sortField = 'budget';

    public string $sortDirection = 'desc';

    /** Champs autorisés pour le tri */
    protected array $allowedSorts = ['name', 'budget', 'spent', 'remaining'];

    public float $totalBudget = 0;

    public float $totalSpent = 0;

    public float $totalRemaining = 0;

    public bool $isOverspent = false;

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

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->allowedSorts, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->loadRows();
    }

    private function loadRows(): void
    {
        $month = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $categories = $this->user->moneyCategories()->orderBy('name')->get();

        $this->rows = [];
        $this->totalBudget = 0;
        $this->totalSpent = 0;
        $this->totalRemaining = 0;

        foreach ($categories as $category) {
            /** @var MoneyCategory $category */
            $spent = $category->spentForMonth($month);
            $remaining = $category->remainingForMonth($month);
            $budget = $category->budget !== null ? (float) $category->budget : null;

            $this->rows[] = [
                'category' => $category,
                'budget' => $budget,
                'spent' => (float) $spent,
                'remaining' => $remaining,
                'overspent' => $category->isOverspentForMonth($month),
            ];

            // Calcul des totaux - SEULEMENT pour les catégories avec budget défini
            if ($budget !== null) {
                $this->totalBudget += $budget;
                $this->totalSpent += (float) $spent;
                if ($remaining !== null) {
                    $this->totalRemaining += $remaining;
                }
            }
        }

        $this->isOverspent = $this->totalRemaining < 0;

        // Tri des données
        usort($this->rows, function ($a, $b) {
            $field = $this->sortField;
            $direction = $this->sortDirection === 'asc' ? 1 : -1;

            // Récupération des valeurs selon le type de champ
            if ($field === 'name') {
                $aValue = $a['category']->name;
                $bValue = $b['category']->name;
            } else {
                $aValue = $a[$field];
                $bValue = $b[$field];
            }

            // Si les deux valeurs sont NULL, elles sont égales
            if ($aValue === null && $bValue === null) {
                return 0;
            }

            // NULL va toujours en dernier, peu importe la direction du tri
            if ($aValue === null) {
                return 1; // NULL toujours après
            }
            if ($bValue === null) {
                return -1; // NULL toujours après
            }

            // Tri numérique pour les valeurs numériques
            if (in_array($field, ['budget', 'spent', 'remaining'])) {
                return ($aValue <=> $bValue) * $direction;
            }

            // Tri alphabétique pour les autres champs
            return strcmp($aValue, $bValue) * $direction;
        });
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
