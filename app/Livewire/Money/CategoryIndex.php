<?php

namespace App\Livewire\Money;

use App\Http\Livewire\Traits\Notifies;
use App\Services\CategoryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CategoryIndex extends Component
{
    use Notifies;

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

    public function updateCategoryName($newName, $categoryId, CategoryService $categoryService)
    {
        $cat = $this->user->moneyCategories()->find($categoryId);
        if ($cat) {
            $categoryService->updateCategory($cat, ['name' => $newName]);
            $this->notifySuccess('Nom mis à jour.');
            $this->refreshList();
        } else {
            $this->notifyError('Catégorie introuvable.');
        }
    }

    public function updateCategoryBudget($newBudget, $categoryId, CategoryService $categoryService)
    {
        $cat = $this->user->moneyCategories()->find($categoryId);
        if ($cat) {
            $categoryService->updateCategory($cat, ['budget' => $newBudget]);
            $this->notifySuccess('Budget mis à jour.');
            $this->refreshList();
        } else {
            $this->notifyError('Catégorie introuvable.');
        }
    }

    public function updateCategoryColor($newColor, $categoryId, CategoryService $categoryService)
    {
        $cat = $this->user->moneyCategories()->find($categoryId);
        if ($cat) {
            $categoryService->updateCategory($cat, ['color' => $newColor]);
            $this->notifySuccess('Couleur mise à jour.');
            $this->refreshList();
        } else {
            $this->notifyError('Catégorie introuvable.');
        }
    }

    public function deleteCategory($categoryId, CategoryService $categoryService)
    {
        $cat = $this->user->moneyCategories()->find($categoryId);
        if ($cat) {
            $categoryService->deleteCategory($cat);
            $this->notifySuccess('Catégorie supprimée.');
            $this->refreshList();
        } else {
            $this->notifyError('Catégorie introuvable.');
        }
    }

    public function addCategory(CategoryService $categoryService)
    {
        $this->validate([
            'newName' => 'required|string|max:255',
            'newBudget' => 'required|numeric|min:0',
            'newColor' => 'required|string',
        ]);

        $categoryService->addCategory([
            'name' => $this->newName,
            'budget' => $this->newBudget,
            'color' => $this->newColor,
        ]);

        $this->notifySuccess('Catégorie ajoutée.');
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
