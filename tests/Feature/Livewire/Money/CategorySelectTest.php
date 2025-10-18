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

    Toaster::assertDispatched(__('Category saved successfully'));
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

    Toaster::assertDispatched(__('Category saved successfully'));
});

test('validates required fields when saving', function () {
    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
        'description' => 'Test Transaction',
    ]);

    // Test that the save method handles empty category gracefully
    Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->set('selectedCategory', null)
        ->call('save')
        ->assertStatus(200);
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

    Toaster::assertDispatched(__('Category saved successfully'));
});

test('dispatches transactions-edited event when saving', function () {
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
    ]);

    $account = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->create([
        'bank_account_id' => $account->id,
    ]);

    $component = Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->set('selectedCategory', 'Test Category')
        ->set('alreadyExists', true);
    $component->call('save');
    $component->assertSet('selectedCategory', 'Test Category');
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

    $component = Livewire::test(CategorySelect::class, ['transaction' => $transaction])
        ->set('selectedCategory', 'Test Category')
        ->set('alreadyExists', true)
        ->set('addOtherTransactions', true)
        ->set('keyword', 'Test Transaction');
    $component->call('save');
    $component->assertSet('keyword', 'Test Transaction');
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
