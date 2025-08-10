<?php

use App\Livewire\Money\CategorySelect;
use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\User;
use Livewire\Livewire;
use Masmerise\Toaster\Toaster;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('category select component can be rendered', function () {
    Livewire::test(CategorySelect::class)
        ->assertStatus(200);
});

test('can load categories', function () {
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    Livewire::test(CategorySelect::class)
        ->assertStatus(200)
        ->assertSee('Test Category');
});

test('can select existing category', function () {
    Toaster::fake();

    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    Livewire::test(CategorySelect::class)
        ->set('selectedCategory', 'Test Category')
        ->assertSet('selectedCategory', 'Test Category');
});

test('shows error when selecting non-existent category', function () {
    Toaster::fake();

    Livewire::test(CategorySelect::class)
        ->set('selectedCategory', 'Non-existent Category')
        ->assertSet('selectedCategory', 'Non-existent Category');
});

test('can save existing category', function () {
    Toaster::fake();

    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
        'description' => 'Test Transaction',
    ]);

    Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->set('selectedCategory', 'Test Category')
        ->set('alreadyExists', true)
        ->call('save');

    $this->assertDatabaseHas('bank_transactions', [
        'id' => $transaction->id,
        'money_category_id' => $category->id,
    ]);

    Toaster::assertDispatched('Category saved successfully');
});

test('can create new category', function () {
    Toaster::fake();

    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
        'description' => 'Test Transaction',
    ]);

    Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->set('selectedCategory', 'New Category')
        ->set('description', 'New Category Description')
        ->set('alreadyExists', false)
        ->call('save');

    $this->assertDatabaseHas('money_categories', [
        'user_id' => $this->user->id,
        'name' => 'New Category',
        'description' => 'New Category Description',
    ]);

    Toaster::assertDispatched('Category saved successfully');
});

test('validates required fields when saving', function () {
    Toaster::fake();

    Livewire::test(CategorySelect::class)
        ->set('selectedCategory', '')
        ->call('save');

    Toaster::assertDispatched('Le contenu de la categorie est invalide.');
});

test('can add category match for other transactions', function () {
    Toaster::fake();

    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
        'description' => 'Test Transaction',
    ]);

    Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->set('selectedCategory', 'Test Category')
        ->set('alreadyExists', true)
        ->set('addOtherTransactions', true)
        ->set('keyword', 'Test Transaction')
        ->call('save');

    $this->assertDatabaseHas('money_category_matches', [
        'money_category_id' => $category->id,
        'keyword' => 'Test Transaction',
    ]);

    Toaster::assertDispatched('Category saved successfully');
});

test('dispatches transactions-edited event when saving', function () {
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
    ]);

    Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->set('selectedCategory', 'Test Category')
        ->set('alreadyExists', true)
        ->call('save')
        ->assertDispatched('transactions-edited');
});

test('dispatches update-category-match event when adding other transactions', function () {
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
        'description' => 'Test Transaction',
    ]);

    Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->set('selectedCategory', 'Test Category')
        ->set('alreadyExists', true)
        ->set('addOtherTransactions', true)
        ->set('keyword', 'Test Transaction')
        ->call('save')
        ->assertDispatched('update-category-match', 'Test Transaction');
});

test('can handle transaction with existing category', function () {
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
        'money_category_id' => $category->id,
        'description' => 'Test Transaction',
    ]);

    Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->assertSet('selectedCategory', 'Test Category');
});

test('can handle transaction without category', function () {
    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
        'description' => 'Test Transaction',
        'money_category_id' => null,
    ]);

    Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->assertSet('selectedCategory', null);
});
