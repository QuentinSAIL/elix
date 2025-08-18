<?php

use App\Livewire\Money\CategoryForm;
use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('category form component can be rendered', function () {
    Livewire::test(CategoryForm::class)
        ->assertStatus(200);
});

test('can reset form', function () {
    Livewire::test(CategoryForm::class)
        ->call('resetForm')
        ->assertSet('categoryForm.name', '')
        ->assertSet('categoryForm.description', '')
        ->assertSet('categoryForm.color', '#f66151')
        ->assertSet('categoryForm.budget', 0)
        ->assertSet('categoryForm.include_in_dashboard', true);
});

test('can populate form for new category', function () {
    Livewire::test(CategoryForm::class)
        ->call('populateForm')
        ->assertSet('edition', false)
        ->assertSet('categoryForm.name', '')
        ->assertSet('categoryForm.color', '#f66151');
});

test('can populate form for existing category', function () {
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
        'description' => 'Test Description',
        'color' => '#ff0000',
        'budget' => 100,
        'include_in_dashboard' => true,
    ]);

    Livewire::test(CategoryForm::class, ['category' => $category])
        ->call('populateForm')
        ->assertSet('edition', true)
        ->assertSet('categoryForm.name', 'Test Category')
        ->assertSet('categoryForm.description', 'Test Description');
});

test('can add category match', function () {
    Livewire::test(CategoryForm::class)
        ->call('addCategoryMatch')
        ->assertSet('categoryMatchForm.0.keyword', '');
});

test('can remove category match', function () {
    Livewire::test(CategoryForm::class)
        ->set('categoryMatchForm', [
            ['id' => '1', 'category_id' => '1', 'keyword' => 'test1'],
            ['id' => '2', 'category_id' => '1', 'keyword' => 'test2'],
        ])
        ->call('removeCategoryMatch', 0)
        ->assertSet('categoryMatchForm.0.keyword', 'test2');
});

test('can save new category', function () {
    Livewire::test(CategoryForm::class)
        ->set('categoryForm', [
            'name' => 'New Category',
            'description' => 'New Description',
            'color' => '#ff0000',
            'budget' => 100,
            'include_in_dashboard' => true,
        ])
        ->call('save');

    $this->assertDatabaseHas('money_categories', [
        'name' => 'New Category',
        'description' => 'New Description',
    ]);
});

test('can save existing category', function () {
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Old Name',
    ]);

    Livewire::test(CategoryForm::class, ['category' => $category])
        ->set('categoryForm.name', 'Updated Name')
        ->call('save');

    $category->refresh();
    $this->assertEquals('Updated Name', $category->name);
});

test('validates required fields', function () {
    Livewire::test(CategoryForm::class)
        ->set('categoryForm.name', '')
        ->call('save')
        ->assertHasErrors(['categoryForm.name' => 'required']);
});

test('can apply match to existing transactions', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();
    $match = MoneyCategoryMatch::factory()->create([
        'money_category_id' => $category->id,
        'user_id' => $this->user->id,
        'keyword' => 'test',
    ]);

    Livewire::test(CategoryForm::class, ['category' => $category])
        ->set('applyMatch', true)
        ->set('applyMatchToAlreadyCategorized', true)
        ->call('applyMatch');
});

test('can detect match changes', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();
    $match = MoneyCategoryMatch::factory()->create([
        'money_category_id' => $category->id,
        'user_id' => $this->user->id,
        'keyword' => 'existing',
    ]);

    Livewire::test(CategoryForm::class, ['category' => $category])
        ->set('categoryMatchForm', [
            ['id' => $match->id, 'category_id' => $category->id, 'keyword' => 'new'],
        ])
        ->assertSet('hasMatchChanges', true);
});

test('can handle category with no matches', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();

    Livewire::test(CategoryForm::class, ['category' => $category])
        ->call('populateForm')
        ->assertSet('categoryMatchForm', [['id' => '', 'category_id' => '', 'keyword' => '']]);
});
