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

    public $categoryForm = [];

    public $categoryMatchForm = [];

    public $originalCategoryMatchForm = [];

    public $applyMatch = true;
    public $applyMatchToAlreadyCategorized = false;

    public $deletedKeywords;

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
            [
                'id' => '',
                'category_id' => '',
                'keyword' => '',
            ]
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
                'color' => $this->category->color,
                'budget' => $this->category->budget,
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
            } else {
                $this->categoryMatchForm = [
                    [
                        'id' => '',
                        'category_id' => '',
                        'keyword' => '',
                    ]
                ];
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
        if (!is_array($this->categoryMatchForm)) {
            $this->categoryMatchForm = [];
            return;
        }

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


        $existingKeywords = MoneyCategoryMatch::where('user_id', $this->user->id)
            ->pluck('keyword')
            ->toArray();

        $collisions = [];
        if (is_array($this->categoryMatchForm)) {
            foreach ($this->categoryMatchForm as $index => $match) {
                if (!is_array($match) || ($match['id'] ?? '') !== "") {
                    continue;
                }
                $keyword = trim($match['keyword'] ?? '');
                if ($keyword !== '') {
                    foreach ($existingKeywords as $existing) {
                        if (
                            stripos($existing, $keyword) !== false ||
                            stripos($keyword, $existing) !== false
                        ) {
                            $collisions[] = [
                                'input' => $keyword,
                                'existing' => $existing,
                            ];
                        }
                    }
                    $rules['categoryMatchForm.' . $index . '.keyword'] = 'required|string|max:255';
                } else {
                    unset($this->categoryMatchForm[$index]);
                }
            }
        }

        if (!empty($collisions)) {
            $messages = [];
            foreach ($collisions as $collision) {
            $messages[] = __('Keyword ":input" collides with existing ":existing".', [
                'input' => $collision['input'],
                'existing' => $collision['existing'],
            ]);
            }
            Toaster::error(implode(' ', $messages), ['duration' => 60]);
            return;
        }

        $this->validate($rules);

        if ($this->edition) {
            $this->category->update($this->categoryForm);

            $deletedKeywords = array_diff($existingKeywords, $newKeywords ?? []);
            foreach ($deletedKeywords as $keyword) {
                $match = $this->category->categoryMatches()->where('keyword', $keyword)->first();
                if ($match) {
                    $match->delete();
                }
            }

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
        if ($this->applyMatch && is_array($this->categoryMatchForm)) {
            $transactionEdited = 0;
            foreach ($this->categoryMatchForm as $match) {
                if (is_array($match) && isset($match['keyword'])) {
                    $transactionEdited += MoneyCategoryMatch::searchAndApplyMatchCategory($match['keyword'], $this->applyMatchToAlreadyCategorized);
                }
            }
            Toaster::success('Category applied to all matching transactions (' . $transactionEdited . ')');
        }
    }

    public function render()
    {
        return view('livewire.money.category-form');
    }
}
