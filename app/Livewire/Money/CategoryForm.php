<?php

namespace App\Livewire\Money;

use App\Http\Livewire\Traits\Notifies;
use App\Models\MoneyCategoryMatch;
use App\Services\CategoryService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CategoryForm extends Component
{
    use Notifies;

    public $user;

    public $edition;

    public $categoryId;

    public $category; // c'est rempli quand on est en edition

    public $categoryForm;

    public $categoryMatchForm;

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
        $newKeywords = array_filter(array_column($this->categoryMatchForm ?? [], 'keyword'));

        foreach ($newKeywords as $keyword) {
            if (! in_array($keyword, $existingKeywords)) {
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
        if ($this->edition && ! empty($match['id'])) {
            $this->category->categoryMatches()->where('id', $match['id'])->delete();
        }

        unset($this->categoryMatchForm[$index]);

        $this->categoryMatchForm = array_values($this->categoryMatchForm);
    }

    public function save(CategoryService $categoryService)
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
            $this->notifyError(implode(' ', $messages), ['duration' => 60]);

            return;
        }

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            $this->notifyError(__('Category content is invalid.'));

            return;
        }

        $transactionEdited = $categoryService->saveCategory(
            $this->categoryForm,
            $this->categoryMatchForm,
            $this->edition,
            $this->category,
            $this->applyMatch,
            $this->applyMatchToAlreadyCategorized
        );

        if ($this->applyMatch) {
            $this->notifySuccess('Category applied to all matching transactions ('.$transactionEdited.')');
        }

        Flux::modals()->close('category-form-'.$this->categoryId);
        $this->dispatch('category-saved');
    }

    public function render()
    {
        return view('livewire.money.category-form');
    }
}
