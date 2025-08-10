<?php

use Livewire\Livewire;
use App\Livewire\Money\DashboardPanelForm;
use App\Models\User;
use App\Models\MoneyDashboard;
use App\Models\MoneyDashboardPanel;
use App\Models\MoneyCategory;
use App\Models\BankAccount;
use Masmerise\Toaster\Toaster;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('dashboard panel form component can be rendered', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->assertStatus(200);
});

test('can load form for creating new panel', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->assertSet('edition', false)
        ->assertSet('title', '')
        ->assertSet('type', '')
        ->assertSet('periodType', '');
});

test('can load form for editing existing panel', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Test Panel',
        'type' => 'bar',
        'period_type' => 'monthly',
    ]);

    Livewire::test(DashboardPanelForm::class, [
        'moneyDashboard' => $dashboard,
        'panel' => $panel,
    ])
        ->assertSet('edition', true)
        ->assertSet('title', 'Test Panel')
        ->assertSet('type', 'bar')
        ->assertSet('periodType', 'monthly');
});

test('can reset form', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->set('title', 'Test Title')
        ->set('type', 'bar')
        ->set('periodType', 'monthly')
        ->set('accountsId', [1, 2, 3])
        ->set('categoriesId', [1, 2, 3])
        ->call('resetForm')
        ->assertSet('title', '')
        ->assertSet('type', '')
        ->assertSet('periodType', '')
        ->assertSet('accountsId', [])
        ->assertSet('categoriesId', []);
});

test('can create new panel', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $category = MoneyCategory::factory()->for($this->user)->create();
    $account = BankAccount::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->set('title', 'New Panel')
        ->set('type', 'bar')
        ->set('periodType', 'monthly')
        ->set('accountsId', [$account->id])
        ->set('categoriesId', [$category->id])
        ->call('save');

    $this->assertDatabaseHas('money_dashboard_panels', [
        'money_dashboard_id' => $dashboard->id,
        'title' => 'New Panel',
        'type' => 'bar',
        'period_type' => 'monthly',
    ]);

    Toaster::assertDispatched('Panel created successfully.');
});

test('can edit existing panel', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Old Title',
        'type' => 'bar',
        'period_type' => 'monthly',
    ]);

    Livewire::test(DashboardPanelForm::class, [
        'moneyDashboard' => $dashboard,
        'panel' => $panel,
    ])
        ->set('title', 'Updated Title')
        ->set('type', 'pie')
        ->set('periodType', 'weekly')
        ->call('save');

    $this->assertDatabaseHas('money_dashboard_panels', [
        'id' => $panel->id,
        'title' => 'Updated Title',
        'type' => 'pie',
        'period_type' => 'weekly',
    ]);

    Toaster::assertDispatched('Panel edited successfully.');
});

test('validates required fields when creating panel', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->set('title', '')
        ->set('type', '')
        ->set('periodType', '')
        ->call('save');

    // Since the component catches validation exceptions and shows toasts,
    // we just verify that the save method completed without creating a panel
    $this->assertDatabaseMissing('money_dashboard_panels', [
        'money_dashboard_id' => $dashboard->id,
        'title' => '',
    ]);
});

test('validates type field values', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->set('title', 'Test Panel')
        ->set('type', 'invalid_type')
        ->set('periodType', 'monthly')
        ->call('save');

    Toaster::assertDispatched('The selected type is invalid.');
});

test('validates period type field values', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->set('title', 'Test Panel')
        ->set('type', 'bar')
        ->set('periodType', 'invalid_period')
        ->call('save');

    Toaster::assertDispatched('The selected period type is invalid.');
});

test('validates account ids exist', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->set('title', 'Test Panel')
        ->set('type', 'bar')
        ->set('periodType', 'monthly')
        ->set('accountsId', ['00000000-0000-0000-0000-000000000000'])
        ->call('save');

    // Since the component catches validation exceptions and shows toasts,
    // we just verify that the save method completed without creating a panel
    $this->assertDatabaseMissing('money_dashboard_panels', [
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Test Panel',
    ]);
});

test('validates category ids exist', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->set('title', 'Test Panel')
        ->set('type', 'bar')
        ->set('periodType', 'monthly')
        ->set('categoriesId', ['00000000-0000-0000-0000-000000000000'])
        ->call('save');

    // Since the component catches validation exceptions and shows toasts,
    // we just verify that the save method completed without creating a panel
    $this->assertDatabaseMissing('money_dashboard_panels', [
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Test Panel',
    ]);
});

test('can sync bank accounts and categories', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $category1 = MoneyCategory::factory()->for($this->user)->create();
    $category2 = MoneyCategory::factory()->for($this->user)->create();
    $account1 = BankAccount::factory()->for($this->user)->create();
    $account2 = BankAccount::factory()->for($this->user)->create();

    Livewire::test(DashboardPanelForm::class, ['moneyDashboard' => $dashboard])
        ->set('title', 'Test Panel')
        ->set('type', 'bar')
        ->set('periodType', 'monthly')
        ->set('accountsId', [$account1->id, $account2->id])
        ->set('categoriesId', [$category1->id, $category2->id])
        ->call('save');

    $panel = MoneyDashboardPanel::where('title', 'Test Panel')->first();

    $this->assertNotNull($panel);
    $this->assertEquals(2, $panel->bankAccounts()->count());
    $this->assertEquals(2, $panel->categories()->count());
});
