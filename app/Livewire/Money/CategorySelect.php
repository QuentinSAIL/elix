<?php

namespace App\Livewire\Money;

use Flux\Flux;
use App\Models\MoneyCategory;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CategorySelect extends Component
{
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

    public $mobile = false;


    public function mount()
    {
        $this->user = Auth::user();
        $this->categories = $this->user->moneyCategories;
        $this->selectedCategory = $this->transaction ? $this->transaction->category?->name : null;
        $this->keyword = $this->transaction ? $this->transaction->description : null;
        $this->modalId = $this->transaction ? $this->transaction->id : ($this->selectedCategory ? $this->selectedCategory : 'create-'.Str::random(32));
        $this->modalId .= $this->mobile ? '-m' : '';
    }

    public function updatedSelectedCategory($value)
    {
        $category = $this->user->moneyCategories()->where('name', $value)->first();
        if (! $category) {
            $this->alreadyExists = false;
            Toaster::error(__('Category not found'));
        } else {
            $this->alreadyExists = true;
            Toaster::success(__('Category found'));
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
            Toaster::error(__('The category content is invalid.'));

            return;
        }

        if ($this->alreadyExists) {
            $category = $this->user->moneyCategories()->where('name', $this->selectedCategory)->first();
        } else {
            $category = $this->user->moneyCategories()->create([
                'name' => $this->selectedCategory,
                'description' => $this->description,
            ]);
        }

        if ($category) {
            $this->transaction->category()->associate($category)->save();
            $this->transaction->refresh();

            // Émettre l'événement pour mettre à jour cette transaction spécifique
            $this->dispatch('transaction-categorized', $this->transaction->id);
        }

        if ($this->addOtherTransactions) {
            $this->user->moneyCategoryMatches()->updateOrCreate(
                [
                    'keyword' => $this->keyword,
                ],
                [
                    'money_category_id' => $category->id,
                ],
            );

            $this->dispatch('update-category-match', $this->keyword);
        }

        $this->dispatch('transactions-edited');

        if ($this->transaction) {
            Flux::modals()->close('category-form-'.$this->transaction->id);
        }
        Toaster::success(__('Category saved successfully'));
    }

    public function render()
    {
        return view('livewire.money.category-select');
    }
}
