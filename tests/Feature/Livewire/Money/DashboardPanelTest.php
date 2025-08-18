<?php

use App\Livewire\Money\DashboardPanel;
use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\MoneyDashboard;
use App\Models\MoneyDashboardPanel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('dashboard panel component can be rendered', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $dashboard->id]);

    Livewire::test(DashboardPanel::class, ['panel' => $panel])
        ->assertStatus(200);
});

test('can mount with panel', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Test Panel',
        'is_expense' => true, // Explicitly set to true for this test
    ]);

    Livewire::test(DashboardPanel::class, ['panel' => $panel])
        ->assertSet('title', 'Test Panel')
        ->assertSet('isExpensePanel', true);
});

test('can prepare chart data for expenses', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'is_expense' => true,
    ]);
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Test Category',
        'color' => '#ff0000',
    ]);
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'amount' => -100,
        'money_category_id' => $category->id,
        'transaction_date' => now(),
    ]);

    $panel->categories()->attach($category);
    $panel->bankAccounts()->attach($bankAccount);

    Livewire::test(DashboardPanel::class, ['panel' => $panel])
        ->assertSet('labels', ['Test Category'])
        ->assertSet('values', [-100])
        ->assertSet('colors', ['#ff0000']);
});

test('can prepare chart data for income', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'is_expense' => false,
    ]);
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Income Category',
        'color' => '#00ff00',
    ]);
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'amount' => 200, // Positive for income
        'money_category_id' => $category->id,
        'transaction_date' => now(),
    ]);

    $panel->categories()->attach($category);
    $panel->bankAccounts()->attach($bankAccount);

    Livewire::test(DashboardPanel::class, ['panel' => $panel])
        ->assertSet('labels', ['Income Category'])
        ->assertSet('values', [200])
        ->assertSet('colors', ['#00ff00']);
});

test('can handle uncategorized transactions', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'is_expense' => true,
    ]);
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $uncategorized = MoneyCategory::factory()->for($this->user)->create([
        'name' => 'Uncategorized',
        'color' => '#CCCCCC',
    ]);
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'amount' => -50,
        'money_category_id' => $uncategorized->id,
        'transaction_date' => now()->startOfDay(),
    ]);

    $panel->bankAccounts = collect([$bankAccount]); // Explicitly set the bankAccounts relationship

    $component = Livewire::test(DashboardPanel::class, ['panel' => $panel])
        ->set('displayUncategorized', true);

    $this->assertContains('Uncategorized', $component->get('labels'));
    $this->assertContains(-50, $component->get('values'));
    $this->assertContains('#CCCCCC', $component->get('colors'));
});

test('can assign date range', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'period_type' => 'daily',
    ]);

    Livewire::test(DashboardPanel::class, ['panel' => $panel])
        ->assertSet('startDate', now()->startOfDay()->format('Y-m-d'))
        ->assertSet('endDate', now()->endOfDay()->format('Y-m-d'));
});

test('can get transactions with filters', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'is_expense' => true, // Explicitly set to true
    ]);
    $categoryName = 'Test Category for Filter'; // Explicitly define category name
    $category = MoneyCategory::factory()->for($this->user)->create([
        'name' => $categoryName, // Use the explicit name
    ]);
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'money_category_id' => $category->id,
        'transaction_date' => now()->startOfDay(),
        'amount' => -100, // Ensure it's an expense
    ]);

    // Attach category and bank account to the panel in the database
    $panel->categories()->attach($category);
    $panel->bankAccounts()->attach($bankAccount);

    $component = Livewire::test(DashboardPanel::class, ['panel' => $panel]);

    $this->assertContains($categoryName, $component->get('labels')); // Assert with explicit name
    $this->assertContains($transaction->amount, $component->get('values'));
});

test('can edit panel', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $dashboard->id]);

    Livewire::test(DashboardPanel::class, ['panel' => $panel])
        ->call('edit')
        ->assertDispatched('edit-panel', $panel->id);
});
