<?php

namespace App\Livewire\Money;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;
use App\Models\MoneyCategoryMatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CategorySelect extends Component
{
    public $user;

    public $categories;

    public $category;

    public $transaction;

    public $selectedCategory;

    public $alreadyExists = true;

    public $addOtherTransactions = false;

    public $addOnlyFutureTransactions = false;

    public $categoryForm;

    public $isExpense;

    public $keyword;

    public $description;

    public $modalId;

    public function mount()
    {
        $this->user = Auth::user();
        $this->categories = $this->user->moneyCategories;
        $this->selectedCategory = $this->transaction ? $this->transaction->category?->name : null;
        $this->keyword = $this->transaction ? $this->transaction->description : null;
        $this->isExpense = $this->transaction ? $this->transaction->amount < 0 : true;
        $this->modalId = $this->transaction ? $this->transaction->id : ($this->selectedCategory ? $this->selectedCategory : 'create-' . Str::random(32));
    }

    public function updatedSelectedCategory($value)
    {
        $category = $this->user->moneyCategories()->where('name', $value)->first();
        if (!$category) {
            $this->alreadyExists = false;
            Toaster::error('Category not found');
        } else {
            $this->alreadyExists = true;
            Toaster::success('Category found');
        }
    }

    public function save()
    {
        $rules = [
            'selectedCategory' => 'required|string|max:255',
        ];

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            Toaster::error('Le contenu de la categorie est invalide.');
            return;
        }

        if ($this->alreadyExists) {
            $category = $this->user->moneyCategories()->where('name', $this->selectedCategory)->first();
        } else {
            $category = $this->user->moneyCategories()->create([
                'name' => $this->selectedCategory,
                'description' => $this->description,
                'is_expense' => $this->isExpense,
            ]);
        }

        if ($category) {
            $this->transaction->category()->associate($category)->save();
        }

        if ($this->addOtherTransactions) {
            $this->user->moneyCategoryMatches()->updateOrCreate([
                'keyword' => $this->keyword,
                'user_id' => $this->user->id,
            ], [
                'keyword' => $this->keyword,
                'money_category_id' => $category->id,
                'user_id' => $this->user->id,
            ]);

            if (!$this->addOnlyFutureTransactions) {
                $transactionEdited = MoneyCategoryMatch::searchAndApplyCategory();
                Toaster::success('Category applied to all matching transactions (' . $transactionEdited . ')');
            }
        }

        $this->dispatch('transactions-edited');

        if ($this->transaction) {
            Flux::modals()->close('transaction-form-' . $this->transaction->id);
        }
        Toaster::success('Category saved successfully');
    }

    public function render()
    {
        return view('livewire.money.category-select');
    }
}
