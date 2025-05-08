<?php

namespace App\Livewire\Money;

use Flux\Flux;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CategoryForm extends Component
{
    public $user;

    public $transaction;

    public $category;

    public $categoryId;

    public $edition;

    public $categoryForm = [];

    public function mount()
    {
        $this->user = Auth::user();
        if ($this->category) {
            $this->categoryId = $this->category->id;
            $this->edition = true;
            $this->categoryForm = [
                'name' => $this->category->name,
                'description' => $this->category->description,
                'is_expense' => $this->category->is_expense,
            ];
        } else {
            $this->categoryId = 'create-' . $this->transaction ? $this->transaction->id : Str::random(32);
            $this->edition = false;
        }

        $this->resetForm();
    }

    #[On('category-form-save')]
    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            Toaster::error('Le contenu de la categorie est invalide.');
            return;
        }

        // on crée la catégorie
        if ($this->edition) {
            $this->category->update([
                'name' => $this->categoryForm['name'],
                'description' => $this->categoryForm['description'],
                'is_expense' => $this->categoryForm['is_expense'],
            ]);
        } else {
            $this->category = $this->user->moneyCategories()->create([
                'name' => $this->categoryForm['name'],
                'description' => $this->categoryForm['description'],
                'is_expense' => $this->categoryForm['is_expense'],
            ]);
        }
        // on l'associe à la transaction si il y en a une
        if ($this->transaction) {
            $this->transaction->category()->associate($this->category);
            $this->transaction->save();
            $this->transaction->refresh();
        }

        Flux::modals()->close('category-form-' . $this->categoryId);
        $this->dispatch('category-saved', category: $this->category);
    }

    protected function resetForm()
    {
        if ($this->transaction) {
            $isExpense = $this->transaction->amount < 0 ? true : false;
        }

        if ($this->edition) {
            $this->categoryForm = [
                'name' => $this->category->name,
                'description' => $this->category->description,
                'is_expense' => $this->category->is_expense,
            ];
        } else {
            $this->categoryForm = [
                'name' => '',
                'description' => '',
                'is_expense' => $isExpense ?? true,
            ];
        }
    }

    public function render()
    {
        return view('livewire.money.category-form');
    }
}
