<?php

namespace App\Livewire\Money;

use App\Models\MoneyCategoryMatch;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CategoryForm extends Component
{
    public \App\Models\User $user;

    public bool $edition;

    public string|int $categoryId;

    public ?\App\Models\MoneyCategory $category = null; // c'est rempli quand on est en edition

    /** @var array<string, string|float|bool> */
    public array $categoryForm = [];

    /** @var array<int, array{id: string|null, category_id: string|null, keyword: string}> */
    public array $categoryMatchForm = [];

    /** @var array<int, array{id: string|null, category_id: string|null, keyword: string}> */
    public array $originalCategoryMatchForm = [];

    public bool $applyMatch = true;

    public bool $applyMatchToAlreadyCategorized = false;

    /** @var array<string> */
    public array $deletedKeywords;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->populateForm();
    }

    public function resetForm(): void
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
            ],
        ];

        $this->originalCategoryMatchForm = $this->categoryMatchForm;
    }

    public function populateForm(): void
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
                /** @var \App\Models\MoneyCategoryMatch $match */
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
                    ],
                ];
            }
            $this->originalCategoryMatchForm = $this->categoryMatchForm;
        } else {
            $this->resetForm();
            $this->categoryId = 'create-'.uniqid();
            $this->edition = false;
        }
    }

    public function getHasMatchChangesProperty(): bool
    {
        if (! $this->edition) {
            return false;
        }

        $existingKeywords = $this->category->categoryMatches->pluck('keyword')->toArray();
        $newKeywords = array_filter(array_column($this->categoryMatchForm, 'keyword'));

        foreach ($newKeywords as $keyword) {
            if (! in_array($keyword, $existingKeywords)) {
                return true;
            }
        }

        return false;
    }

    public function addCategoryMatch(): void
    {
        $this->categoryMatchForm[] = [
            'id' => '',
            'category_id' => '',
            'keyword' => '',
        ];
    }

    public function removeCategoryMatch(int $index): void
    {
        $match = $this->categoryMatchForm[$index] ?? null;
        if ($this->edition && ! empty($match['id'])) {
            $this->category->categoryMatches()->where('id', $match['id'])->delete();
        }

        unset($this->categoryMatchForm[$index]);

        $this->categoryMatchForm = array_values($this->categoryMatchForm);
    }

    public function save(): void
    {
        $rules = [
            'categoryForm.name' => 'required|string|max:255',
        ];

        $existingKeywords = MoneyCategoryMatch::where('user_id', $this->user->id)
            ->pluck('keyword')
            ->toArray();

        $collisions = [];
        foreach ($this->categoryMatchForm as $index => $match) {
            if ($match['id'] !== '') {
                continue;
            }
            $keyword = trim($match['keyword']);
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
                $rules['categoryMatchForm.'.$index.'.keyword'] = 'required|string|max:255';
            } else {
                unset($this->categoryMatchForm[$index]);
            }
        }

        if (! empty($collisions)) {
            $messages = [];
            foreach ($collisions as $collision) {
                $messages[] = __('Keyword ":input" collides with existing ":existing".', [
                    'input' => $collision['input'],
                    'existing' => $collision['existing'],
                ]);
            }
            $this->dispatch('show-toast', type: 'error', message: implode(' ', $messages));

            return;
        }

        $this->validate($rules);

        if ($this->edition) {
            $this->category->update($this->categoryForm);

            $newKeywords = array_filter(array_column($this->categoryMatchForm, 'keyword'));
            $deletedKeywords = array_diff($existingKeywords, $newKeywords);
            foreach ($deletedKeywords as $keyword) {
                $match = $this->category->categoryMatches()->where('keyword', $keyword)->first();
                if ($match) {
                    $match->delete();
                }
            }

            foreach ($this->categoryMatchForm as $index => $match) {
                $keyword = trim($match['keyword']);
                $matchId = (string) $match['id'];

                if ($keyword !== '') {
                    if ($matchId !== '') {
                        $matchModel = $this->category->categoryMatches()->where('id', $matchId)->first();
                        /** @var \App\Models\MoneyCategoryMatch|null $matchModel */
                        if ($matchModel) {
                            $matchModel->user_id = (string) $this->user->id;
                            $matchModel->keyword = $keyword;
                            $matchModel->save();
                        }
                    } else {
                        /** @var \App\Models\MoneyCategoryMatch $created */
                        $created = $this->category->categoryMatches()->create([
                            'user_id' => (string) $this->user->id,
                            'keyword' => $keyword,
                        ]);
                        $this->categoryMatchForm[$index]['id'] = $created->id;
                    }
                } else {
                    unset($this->categoryMatchForm[$index]);
                }
            }

            $this->categoryMatchForm = array_values($this->categoryMatchForm);
        } else {
            $this->user->moneyCategories()->create($this->categoryForm);
        }

        $this->applyMatch();

        Flux::modals()->close('category-form-'.$this->categoryId);
        $this->dispatch('category-saved');
    }

    public function applyMatch()
    {
        if ($this->applyMatch) {
            $transactionEdited = 0;
            foreach ($this->categoryMatchForm as $match) {
                if ($match['keyword'] !== '') {
                    $transactionEdited += MoneyCategoryMatch::searchAndApplyMatchCategory($match['keyword'], $this->applyMatchToAlreadyCategorized);
                }
            }
            $this->dispatch('show-toast', type: 'success', message: 'Category applied to all matching transactions ('.$transactionEdited.')');
        }
    }

    public function render()
    {
        return view('livewire.money.category-form');
    }
}
