<?php

namespace App\Livewire\Money;

use App\Services\CategoryService;
use App\Http\Livewire\Traits\Notifies;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CategorySelect extends Component
{
    use Notifies;

    public $user;

    public $categories;

    public $category;

    public $transaction;

    public $selectedCategory;

    public $alreadyExists = true;

    public $addOtherTransactions = false;

    public $categoryForm;

    public $keyword;

    public $description;

    public $modalId;

    public function mount()
    {
        $this->user = Auth::user();
        $this->categories = $this->user->moneyCategories;
        $this->selectedCategory = $this->transaction ? $this->transaction->category?->name : null;
        $this->keyword = $this->transaction ? $this->transaction->description : null;
        $this->modalId = $this->transaction ? $this->transaction->id : ($this->selectedCategory ? $this->selectedCategory : 'create-'.Str::random(32));
    }

    public function updatedSelectedCategory($value, CategoryService $categoryService)
    {
        $category = $this->user->moneyCategories()->where('name', $value)->first();
        if (! $category) {
            $this->alreadyExists = false;
            $this->notifyError('Category not found');
        } else {
            $this->alreadyExists = true;
            $this->notifySuccess('Category found');
        }
    }

    public function save(CategoryService $categoryService)
    {
        $rules = [
            'selectedCategory' => 'required|string|max:255',
        ];

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            $this->notifyError('Le contenu de la categorie est invalide.');

            return;
        }

        $category = $categoryService->findOrCreateCategory($this->selectedCategory, $this->description);

        if ($category) {
            $categoryService->associateCategoryWithTransaction($this->transaction, $category);
        }

        if ($this->addOtherTransactions) {
            $categoryService->updateOrCreateCategoryMatch($this->keyword, $category);

            $this->dispatch('update-category-match', $this->keyword);
        }

        $this->dispatch('transactions-edited');

        if ($this->transaction) {
            Flux::modals()->close('transaction-form-'.$this->transaction->id);
        }
        $this->notifySuccess('Category saved successfully');
    }

    public function render()
    {
        return view('livewire.money.category-select');
    }
}
