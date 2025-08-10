<?php

use App\Livewire\Money\CategoryIndex;
use App\Models\MoneyCategory;
use App\Models\User;
use Livewire\Livewire;
use Masmerise\Toaster\Toaster;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('money category index component can be rendered', function () {
    Livewire::test(CategoryIndex::class)
        ->assertStatus(200);
});

test('can load categories', function () {
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
        'budget' => 1000,
        'color' => '#ff0000',
    ]);

    Livewire::test(CategoryIndex::class)
        ->assertStatus(200)
        ->assertSee('Test Category');
});

test('can sort categories by field', function () {
    $category1 = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Category A',
        'budget' => 1000,
    ]);
    $category2 = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Category B',
        'budget' => 2000,
    ]);

    Livewire::test(CategoryIndex::class)
        ->call('sortBy', 'budget')
        ->assertSet('sortField', 'budget')
        ->assertSet('sortDirection', 'asc');
});

test('can toggle sort direction', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();

    Livewire::test(CategoryIndex::class)
        ->call('sortBy', 'budget')
        ->call('sortBy', 'budget')
        ->assertSet('sortDirection', 'desc');
});

test('can update category name', function () {
    Toaster::fake();

    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Old Name',
    ]);

    Livewire::test(CategoryIndex::class)
        ->call('updateCategoryName', 'New Name', $category->id);

    $this->assertDatabaseHas('money_categories', [
        'id' => $category->id,
        'name' => 'New Name',
    ]);

    Toaster::assertDispatched('Nom mis à jour.');
});

test('shows error when updating non-existent category name', function () {
    Toaster::fake();

    Livewire::test(CategoryIndex::class)
        ->call('updateCategoryName', 'New Name', '00000000-0000-0000-0000-000000000000');

    Toaster::assertDispatched('Catégorie introuvable.');
});

test('can update category budget', function () {
    Toaster::fake();

    $category = MoneyCategory::factory()->for($this->user)->create([
        'budget' => 1000,
    ]);

    Livewire::test(CategoryIndex::class)
        ->call('updateCategoryBudget', 2000, $category->id);

    $this->assertDatabaseHas('money_categories', [
        'id' => $category->id,
        'budget' => 2000,
    ]);

    Toaster::assertDispatched('Budget mis à jour.');
});

test('shows error when updating non-existent category budget', function () {
    Toaster::fake();

    Livewire::test(CategoryIndex::class)
        ->call('updateCategoryBudget', 2000, '00000000-0000-0000-0000-000000000000');

    Toaster::assertDispatched('Catégorie introuvable.');
});

test('can update category color', function () {
    Toaster::fake();

    $category = MoneyCategory::factory()->for($this->user)->create([
        'color' => '#ff0000',
    ]);

    Livewire::test(CategoryIndex::class)
        ->call('updateCategoryColor', '#00ff00', $category->id);

    $this->assertDatabaseHas('money_categories', [
        'id' => $category->id,
        'color' => '#00ff00',
    ]);

    Toaster::assertDispatched('Couleur mise à jour.');
});

test('shows error when updating non-existent category color', function () {
    Toaster::fake();

    Livewire::test(CategoryIndex::class)
        ->call('updateCategoryColor', '#00ff00', '00000000-0000-0000-0000-000000000000');

    Toaster::assertDispatched('Catégorie introuvable.');
});

test('can delete category', function () {
    Toaster::fake();

    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    Livewire::test(CategoryIndex::class)
        ->call('deleteCategory', $category->id);

    $this->assertDatabaseMissing('money_categories', ['id' => $category->id]);
    Toaster::assertDispatched('Catégorie supprimée.');
});

test('shows error when deleting non-existent category', function () {
    Toaster::fake();

    Livewire::test(CategoryIndex::class)
        ->call('deleteCategory', '00000000-0000-0000-0000-000000000000');

    Toaster::assertDispatched('Catégorie introuvable.');
});

test('can add new category', function () {
    Toaster::fake();

    Livewire::test(CategoryIndex::class)
        ->set('newName', 'New Category')
        ->set('newBudget', 1500)
        ->set('newColor', '#ff0000')
        ->call('addCategory');

    $this->assertDatabaseHas('money_categories', [
        'user_id' => $this->user->id,
        'name' => 'New Category',
        'budget' => 1500,
        'color' => '#ff0000',
    ]);

    Toaster::assertDispatched('Catégorie ajoutée.');
});

test('validates required fields when adding category', function () {
    Livewire::test(CategoryIndex::class)
        ->set('newName', '')
        ->set('newBudget', '')
        ->call('addCategory')
        ->assertHasErrors(['newName', 'newBudget']);
});

test('validates budget is numeric and positive when adding category', function () {
    Livewire::test(CategoryIndex::class)
        ->set('newName', 'Test Category')
        ->set('newBudget', -100)
        ->call('addCategory')
        ->assertHasErrors(['newBudget']);
});

test('can calculate total budget', function () {
    MoneyCategory::factory()->for($this->user)->create(['budget' => 1000]);
    MoneyCategory::factory()->for($this->user)->create(['budget' => 2000]);

    $component = Livewire::test(CategoryIndex::class);

    // Call the computed property method directly
    $totalBudget = $component->instance()->getTotalBudgetProperty();
    $this->assertEquals(3000, $totalBudget);
});

test('resets form after adding category', function () {
    Livewire::test(CategoryIndex::class)
        ->set('newName', 'New Category')
        ->set('newBudget', 1500)
        ->set('newColor', '#ff0000')
        ->call('addCategory')
        ->assertSet('newName', '')
        ->assertSet('newBudget', '')
        ->assertSet('newColor', '#cccccc');
});
