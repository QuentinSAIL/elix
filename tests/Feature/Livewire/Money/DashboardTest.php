<?php

use App\Livewire\Money\Dashboard;
use App\Models\MoneyDashboard;
use App\Models\MoneyDashboardPanel;
use App\Models\User;
use Livewire\Livewire;
use Masmerise\Toaster\Toaster;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('money dashboard component can be rendered', function () {
    Livewire::test(Dashboard::class)
        ->assertStatus(200);
});

test('creates dashboard if user does not have one', function () {
    Livewire::test(Dashboard::class);

    $this->assertDatabaseHas('money_dashboards', [
        'user_id' => $this->user->id,
    ]);
});

test('loads existing dashboard if user has one', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(Dashboard::class)
        ->assertStatus(200);
});

test('can load dashboard panels', function () {
    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Test Panel',
    ]);

    Livewire::test(Dashboard::class)
        ->assertStatus(200)
        ->assertSee('Test Panel');
});

test('can delete panel', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Test Panel',
    ]);

    Livewire::test(Dashboard::class)
        ->call('deletePanel', $panel->id);

    $this->assertDatabaseMissing('money_dashboard_panels', [
        'id' => $panel->id,
    ]);

    Toaster::assertDispatched('Panel deleted successfully.');
});

test('shows error when deleting non-existent panel', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();

    Livewire::test(Dashboard::class)
        ->call('deletePanel', '00000000-0000-0000-0000-000000000000');

    Toaster::assertDispatched('Panel not found.');
});

test('refreshes panels after deletion', function () {
    Toaster::fake();

    $dashboard = MoneyDashboard::factory()->for($this->user)->create();
    $panel1 = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Panel 1',
    ]);
    $panel2 = MoneyDashboardPanel::factory()->create([
        'money_dashboard_id' => $dashboard->id,
        'title' => 'Panel 2',
    ]);

    $component = Livewire::test(Dashboard::class);

    // Verify both panels exist initially
    $this->assertDatabaseHas('money_dashboard_panels', ['id' => $panel1->id]);
    $this->assertDatabaseHas('money_dashboard_panels', ['id' => $panel2->id]);

    // Delete one panel
    $component->call('deletePanel', $panel1->id);

    // Verify only one panel remains
    $this->assertDatabaseMissing('money_dashboard_panels', ['id' => $panel1->id]);
    $this->assertDatabaseHas('money_dashboard_panels', ['id' => $panel2->id]);

    Toaster::assertDispatched('Panel deleted successfully.');
});
