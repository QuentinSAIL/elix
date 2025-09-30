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

    public $walletAmount;

    public $walletUnit;

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
            if ($category->wallet) {
                $this->walletUnit = $category->wallet->unit;
            } else {
                $this->walletUnit = null;
            }
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

            // If category is linked to a wallet, we must update wallet balance
            if ($category->wallet) {
                $wallet = $category->wallet;

                // Determine unit and amount input
                $unit = $this->walletUnit ?: $wallet->unit;
                $amount = (float) ($this->walletAmount ?? 0);

                if ($amount <= 0) {
                    Toaster::error(__('Invalid wallet amount.'));
                } else {
                    // Positive bank transaction amount means income; negative means expense
                    // When assigning to wallet, we add the absolute amount in the wallet's unit (user provided)
                    // Bank transfer example: 100 EUR to Livret A (EUR) => amount=100, unit=EUR
                    // BTC example: 100 EUR to BTC => amount=0.0001, unit=BTC
                    // We do not convert; we trust user input and set the balance delta accordingly
                    $wallet->balance = (string) ((float) $wallet->balance + $amount);
                    if ($unit) {
                        $wallet->unit = $unit;
                    }
                    $wallet->save();
                }
            }
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
