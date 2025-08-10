<?php

namespace App\Livewire\Money;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CategoryIndex extends Component
{
    public $user;

    public $categories;

    public $newName = '';

    public $newBudget = '';

    public $newColor = '#cccccc';

    public $totalBudget = '#cccccc';

    public $sortField = 'budget';

    public $sortDirection = 'desc';

    public function mount()
    {
        $this->user = Auth::user();
        $this->refreshList();
    }

    public function refreshList()
    {
        $query = $this->user->moneyCategories();
        $query = $query->orderBy($this->sortField, $this->sortDirection);
        $this->categories = $query->get();
    }

    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->refreshList();
    }

    public function updateCategoryName($newName, $categoryId)
    {
        $cat = $this->user->moneyCategories()->find($categoryId);
        if ($cat) {
            $cat->update(['name' => $newName]);
            Toaster::success('Nom mis à jour.');
            $this->refreshList();
        } else {
            Toaster::error('Catégorie introuvable.');
        }
    }

    public function updateCategoryBudget($newBudget, $categoryId)
    {
        $cat = $this->user->moneyCategories()->find($categoryId);
        if ($cat) {
            $cat->update(['budget' => $newBudget]);
            Toaster::success('Budget mis à jour.');
            $this->refreshList();
        } else {
            Toaster::error('Catégorie introuvable.');
        }
    }

    public function updateCategoryColor($newColor, $categoryId)
    {
        $cat = $this->user->moneyCategories()->find($categoryId);
        if ($cat) {
            $cat->update(['color' => $newColor]);
            Toaster::success('Couleur mise à jour.');
            $this->refreshList();
        } else {
            Toaster::error('Catégorie introuvable.');
        }
    }

    public function deleteCategory($categoryId)
    {
        $cat = $this->user->moneyCategories()->find($categoryId);
        if ($cat) {
            $cat->delete();
            Toaster::success('Catégorie supprimée.');
            $this->refreshList();
        } else {
            Toaster::error('Catégorie introuvable.');
        }
    }

    public function addCategory()
    {
        $this->validate([
            'newName' => 'required|string|max:255',
            'newBudget' => 'required|numeric|min:0',
            'newColor' => 'required|string',
        ]);

        $this->user->moneyCategories()->create([
            'name' => $this->newName,
            'budget' => $this->newBudget,
            'color' => $this->newColor,
        ]);

        Toaster::success('Catégorie ajoutée.');
        $this->newName = '';
        $this->newBudget = '';
        $this->newColor = '#cccccc';
        $this->refreshList();
    }

    public function getTotalBudgetProperty()
    {
        return $this->categories->sum('budget');
    }

    public function render()
    {
        return view('livewire.money.category-index');
    }
}
