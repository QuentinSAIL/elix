<?php

namespace App\Livewire\Money;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;
use App\Models\MoneyCategoryMatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CategoryForm extends Component
{
    public $user;

    public $edition;

    public $categoryId;

    public $category; // c'est rempli quand on est en edition

    public $categoryForm;

    public $categoryMatchForm;

    public $originalCategoryMatchForm = [];

    public $applyMatch = true;
    public $applyMatchToAlreadyCategorized = false;

    public function mount()
    {
        $this->user = Auth::user();
        $this->populateForm();
    }

    public function resetForm()
    {
        $this->categoryForm = [
            'name' => '',
            'description' => '',
            'color' => '#f66151',
            'budget' => 0,
            'include_in_dashboard' => true,
        ];

        $this->categoryMatchForm = [
            'id' => '',
            'category_id' => '',
            'keyword' => '',
        ];

        $this->originalCategoryMatchForm = $this->categoryMatchForm;
    }

    public function populateForm()
    {
        if ($this->category) {
            $this->categoryId = $this->category->id;
            $this->edition = true;
            $this->categoryForm = [
                'name' => $this->category->name,
                'description' => $this->category->description,
                'color' => $this->category->duration,
                'budget' => $this->category->order,
                'include_in_dashboard' => $this->category->include_in_dashboard,
            ];

            if ($this->category->categoryMatches->count() > 0) {
                $this->categoryMatchForm = [];
                foreach ($this->category->categoryMatches as $match) {
                    $this->categoryMatchForm[] = [
                        'id' => $match->id,
                        'category_id' => $match->id,
                        'keyword' => $match->keyword,
                    ];
                }
            }
            $this->originalCategoryMatchForm = $this->categoryMatchForm;
        } else {
            $this->resetForm();
            $this->categoryId = 'create-' . uniqid();
            $this->edition = false;
        }
    }

    public function getHasMatchChangesProperty(): bool
    {
        if (!$this->edition) {
            return false;
        }

        $existingKeywords = $this->category->categoryMatches->pluck('keyword')->toArray();
        $newKeywords = array_filter(array_column($this->categoryMatchForm ?? [], 'keyword'));

        foreach ($newKeywords as $keyword) {
            if (!in_array($keyword, $existingKeywords)) {
                return true;
            }
        }

        return false;
    }

    public function addCategoryMatch()
    {
        $this->categoryMatchForm[] = [
            'id' => '',
            'category_id' => '',
            'keyword' => '',
        ];
    }

    public function removeCategoryMatch($index)
    {
        $match = $this->categoryMatchForm[$index] ?? null;
        if ($this->edition && !empty($match['id'])) {
            $this->category->categoryMatches()->where('id', $match['id'])->delete();
        }

        unset($this->categoryMatchForm[$index]);

        $this->categoryMatchForm = array_values($this->categoryMatchForm);
    }

    public function save()
    {
        $rules = [
            'categoryForm.name' => 'required|string|max:255',
        ];

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            Toaster::error(__('Category content is invalid.'));
            return;
        }

        if ($this->edition) {
            $this->category->update($this->categoryForm);

            foreach ($this->categoryMatchForm as $index => $match) {
                $keyword = trim($match['keyword'] ?? '');
                $matchId = $match['id'] ?? null;

                if ($keyword !== '') {
                    if (!empty($matchId)) {
                        $this->category->categoryMatches()->updateOrCreate(
                            ['id' => $matchId],
                            [
                                'user_id' => $this->user->id,
                                'keyword' => $keyword,
                            ],
                        );
                    } else {
                        $created = $this->category->categoryMatches()->create([
                            'user_id' => $this->user->id,
                            'keyword' => $keyword,
                        ]);
                        $this->categoryMatchForm[$index]['id'] = $created->id;
                    }
                } else {
                    if (!empty($matchId)) {
                        $this->category->categoryMatches()->where('id', $matchId)->delete();
                    }
                    unset($this->categoryMatchForm[$index]);
                }
            }

            $this->categoryMatchForm = array_values($this->categoryMatchForm);
        } else {
            $this->user->moneyCategories()->create($this->categoryForm);
        }

        $this->applyMatch();

        Flux::modals()->close('category-form-' . $this->categoryId);
        $this->dispatch('category-saved');
    }

    public function applyMatch()
    {
        if ($this->applyMatch) {
            $transactionEdited = 0;
            foreach ($this->categoryMatchForm as $match) {
                $transactionEdited += MoneyCategoryMatch::searchAndApplyMatchCategory($match['keyword'], $this->applyMatchToAlreadyCategorized);
            }
            Toaster::success('Category applied to all matching transactions (' . $transactionEdited . ')');
        }
    }

    public function render()
    {
        return view('livewire.money.category-form');
    }
}
