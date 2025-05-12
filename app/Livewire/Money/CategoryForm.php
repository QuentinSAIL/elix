<?php

namespace App\Livewire\Money;

use Flux\Flux;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CategoryForm extends Component
{
    public $user;
    public $edition = false;
    public $categoryId;
    public $category;
    public array $categoryForm = [];
    public array $categoryMatchForm = [];

    protected $rules = [
        'categoryForm.name' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->user = Auth::user();
        $this->resetForm();
        if ($this->category) {
            $this->loadCategory();
        }
    }

    private function resetForm(): void
    {
        $this->categoryForm = [
            'name' => '',
            'description' => '',
            'color' => '#f66151',
            'budget' => 0,
            'include_in_dashboard' => false,
        ];
        $this->categoryMatchForm = [];
    }

    private function loadCategory(): void
    {
        $this->edition = true;
        $this->categoryId = $this->category->id;
        $this->categoryForm = [
            'name' => $this->category->name,
            'description' => $this->category->description,
            'color' => $this->category->duration,
            'budget' => $this->category->order,
            'include_in_dashboard' => $this->category->include_in_dashboard,
        ];

        $this->categoryMatchForm = $this->category->categoryMatches
            ->map(fn($m) => [
                'id' => $m->id,
                'keyword' => $m->keyword,
            ])
            ->toArray();
    }

    public function addCategoryMatch(): void
    {
        $this->categoryMatchForm[] = [
            'id' => null,
            'keyword' => '',
        ];
    }

    public function removeCategoryMatch(int $index): void
    {
        $match = $this->categoryMatchForm[$index] ?? null;
        if ($this->edition && !empty($match['id'])) {
            $this->category->categoryMatches()->where('id', $match['id'])->delete();
        }

        unset($this->categoryMatchForm[$index]);
        $this->categoryMatchForm = array_values($this->categoryMatchForm);
    }

    public function save(): void
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            Toaster::error(__('Category content is invalid.'));
            return;
        }

        if ($this->edition) {
            $this->updateCategory();
        } else {
            $this->createCategory();
        }

        Flux::modals()->close('category-form-' . ($this->categoryId ?? 'create'));
        $this->dispatch('category-saved');
    }

    private function createCategory(): void
    {
        $this->category = $this->user->moneyCategories()->create($this->categoryForm);
        $this->categoryId = $this->category->id;
        $this->syncMatches();
    }

    private function updateCategory(): void
    {
        $this->category->update($this->categoryForm);
        $this->syncMatches();
    }

    private function syncMatches(): void
    {
        $matches = collect($this->categoryMatchForm)
            ->filter(fn($m) => trim($m['keyword'] ?? '') !== '')
            ->values();

        foreach ($matches as $data) {
            $attributes = ['user_id' => $this->user->id, 'keyword' => trim($data['keyword'])];

            if (!empty($data['id'])) {
                $this->category->categoryMatches()
                    ->updateOrCreate(['id' => $data['id']], $attributes);
            } else {
                $this->category->categoryMatches()->create($attributes);
            }
        }

        $idsToKeep = $matches->pluck('id')->filter()->all();
        if (!empty($idsToKeep)) {
            $this->category->categoryMatches()
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        } elseif ($this->edition) {
            $this->category->categoryMatches()->delete();
        }

        $this->categoryMatchForm = $this->category->categoryMatches
            ->map(fn($m) => ['id' => $m->id, 'keyword' => $m->keyword])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.money.category-form');
    }
}
