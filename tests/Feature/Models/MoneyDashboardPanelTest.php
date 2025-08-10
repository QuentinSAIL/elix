<?php

use App\Models\User;
use App\Models\MoneyDashboard;
use App\Models\MoneyDashboardPanel;
use App\Models\BankAccount;
use App\Models\MoneyCategory;
use App\Models\BankTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('money dashboard panel belongs to dashboard', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $dashboard->id]);

    $this->assertInstanceOf(MoneyDashboard::class, $panel->dashboard);
    $this->assertEquals($dashboard->id, $panel->dashboard->id);
});

test('money dashboard panel can have bank accounts', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $dashboard->id]);
    $bankAccount = BankAccount::factory()->for($this->user)->create();

    $panel->bankAccounts()->attach($bankAccount);

    $this->assertCount(1, $panel->bankAccounts);
    $this->assertEquals($bankAccount->id, $panel->bankAccounts->first()->id);
});

test('money dashboard panel can have categories', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $dashboard->id]);
    $category = MoneyCategory::factory()->for($this->user)->create();

    $panel->categories()->attach($category);

    $this->assertCount(1, $panel->categories);
    $this->assertEquals($category->id, $panel->categories->first()->id);
});

test('can determine daily period', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'period_type' => 'daily',
    ]);

    $period = $panel->determinePeriode();

    $this->assertNotNull($period['startDate']);
    $this->assertNotNull($period['endDate']);
    $this->assertEquals(now()->startOfDay()->format('Y-m-d'), $period['startDate']->format('Y-m-d'));
    $this->assertEquals(now()->endOfDay()->format('Y-m-d'), $period['endDate']->format('Y-m-d'));
});

test('can determine weekly period', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'period_type' => 'weekly',
    ]);

    $period = $panel->determinePeriode();

    $this->assertNotNull($period['startDate']);
    $this->assertNotNull($period['endDate']);
    $this->assertEquals(now()->startOfWeek()->format('Y-m-d'), $period['startDate']->format('Y-m-d'));
    $this->assertEquals(now()->endOfWeek()->format('Y-m-d'), $period['endDate']->format('Y-m-d'));
});

test('can determine monthly period', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'period_type' => 'monthly',
    ]);

    $period = $panel->determinePeriode();

    $this->assertNotNull($period['startDate']);
    $this->assertNotNull($period['endDate']);
    $this->assertEquals(now()->startOfMonth()->format('Y-m-d'), $period['startDate']->format('Y-m-d'));
    $this->assertEquals(now()->endOfMonth()->format('Y-m-d'), $period['endDate']->format('Y-m-d'));
});

test('can determine yearly period', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'period_type' => 'yearly',
    ]);

    $period = $panel->determinePeriode();

    $this->assertNotNull($period['startDate']);
    $this->assertNotNull($period['endDate']);
    $this->assertEquals(now()->startOfYear()->format('Y-m-d'), $period['startDate']->format('Y-m-d'));
    $this->assertEquals(now()->endOfYear()->format('Y-m-d'), $period['endDate']->format('Y-m-d'));
});

test('can determine all period', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'period_type' => 'all',
    ]);

    $period = $panel->determinePeriode();

    $this->assertNull($period['startDate']);
    $this->assertNull($period['endDate']);
});

test('can get transactions with filters', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $dashboard->id]);
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $category = MoneyCategory::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'money_category_id' => $category->id,
        'transaction_date' => now(),
    ]);

    $transactions = $panel->getTransactions(
        now()->startOfDay(),
        now()->endOfDay(),
        [
            'accounts' => [$bankAccount->id],
            'categories' => [$category->id],
        ]
    );

    $this->assertCount(1, $transactions);
    $this->assertEquals($transaction->id, $transactions->first()->id);
});
