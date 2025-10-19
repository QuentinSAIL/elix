<?php

namespace Tests\Feature\Livewire\Money;

use App\Livewire\Money\BudgetIndex;
use App\Models\BankAccount;
use App\Models\MoneyCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_index_component_can_be_rendered()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(BudgetIndex::class)
            ->assertStatus(200);
    }

    public function test_loads_rows_with_categories_and_calculations()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $bankAccount = BankAccount::factory()->for($user)->create();

        $category = MoneyCategory::factory()->for($user)->create(['budget' => 1000]);
        $category->transactions()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => now(),
            'amount' => -100,
            'description' => 'test',
        ]);

        Livewire::test(BudgetIndex::class)
            ->assertSet('rows.0.category.id', $category->id)
            ->assertSet('rows.0.budget', 1000.0)
            ->assertSet('rows.0.spent', -100.0)
            ->assertSet('rows.0.remaining', 900.0)
            ->assertSet('rows.0.overspent', false);
    }

    public function test_can_navigate_to_next_and_previous_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(BudgetIndex::class)
            ->call('nextMonth')
            ->assertSet('month', now()->addMonth()->format('Y-m'))
            ->call('prevMonth')
            ->assertSet('month', now()->format('Y-m'));
    }

    public function test_can_go_to_current_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(BudgetIndex::class)
            ->set('month', now()->subYear()->format('Y-m'))
            ->call('goToCurrentMonth')
            ->assertSet('month', now()->format('Y-m'));
    }

    public function test_updated_month_reloads_rows()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $bankAccount = BankAccount::factory()->for($user)->create();

        $category = MoneyCategory::factory()->for($user)->create(['budget' => 500]);
        $category->transactions()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => now()->subMonth(),
            'amount' => -50,
            'description' => 'test',
        ]);

        Livewire::test(BudgetIndex::class)
            ->set('month', now()->subMonth()->format('Y-m'))
            ->assertSet('rows.0.spent', -50.0)
            ->set('month', now()->format('Y-m'))
            ->assertSet('rows.0.spent', 0.0);
    }

    public function test_can_sort_by_different_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category1 = MoneyCategory::factory()->for($user)->create(['name' => 'Category A', 'budget' => 100]);
        $category2 = MoneyCategory::factory()->for($user)->create(['name' => 'Category B', 'budget' => 200]);

        $component = Livewire::test(BudgetIndex::class);

        // Test sorting by name
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc');

        // Test sorting by budget
        $component->call('sortBy', 'budget')
            ->assertSet('sortField', 'budget')
            ->assertSet('sortDirection', 'asc');

        // Test sorting by spent
        $component->call('sortBy', 'spent')
            ->assertSet('sortField', 'spent')
            ->assertSet('sortDirection', 'asc');

        // Test sorting by remaining
        $component->call('sortBy', 'remaining')
            ->assertSet('sortField', 'remaining')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_can_toggle_sort_direction()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        MoneyCategory::factory()->for($user)->create(['name' => 'Category A']);

        $component = Livewire::test(BudgetIndex::class);

        // First sort by name
        $component->call('sortBy', 'name')
            ->assertSet('sortDirection', 'asc');

        // Sort by same field again to toggle direction
        $component->call('sortBy', 'name')
            ->assertSet('sortDirection', 'desc');

        // Sort by same field again to toggle back
        $component->call('sortBy', 'name')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_ignores_invalid_sort_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        MoneyCategory::factory()->for($user)->create();

        $component = Livewire::test(BudgetIndex::class);
        $originalSortField = $component->get('sortField');
        $originalSortDirection = $component->get('sortDirection');

        // Try to sort by invalid field
        $component->call('sortBy', 'invalid_field')
            ->assertSet('sortField', $originalSortField)
            ->assertSet('sortDirection', $originalSortDirection);
    }

    public function test_handles_null_values_in_sorting()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create categories with null budgets
        $category1 = MoneyCategory::factory()->for($user)->create(['name' => 'Category A', 'budget' => null]);
        $category2 = MoneyCategory::factory()->for($user)->create(['name' => 'Category B', 'budget' => 100]);

        $component = Livewire::test(BudgetIndex::class);

        // Sort by budget (should handle null values)
        $component->call('sortBy', 'budget')
            ->assertSet('sortField', 'budget');
    }

    public function test_calculates_totals_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $bankAccount = BankAccount::factory()->for($user)->create();

        $category1 = MoneyCategory::factory()->for($user)->create(['budget' => 1000]);
        $category2 = MoneyCategory::factory()->for($user)->create(['budget' => 500]);

        // Add transactions
        $category1->transactions()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => now(),
            'amount' => -200,
            'description' => 'test1',
        ]);

        $category2->transactions()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => now(),
            'amount' => -100,
            'description' => 'test2',
        ]);

        Livewire::test(BudgetIndex::class)
            ->assertSet('totalBudget', 1500.0)
            ->assertSet('totalSpent', -300.0)
            ->assertSet('totalRemaining', 1200.0)
            ->assertSet('isOverspent', false);
    }

    public function test_detects_overspent_categories()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $bankAccount = BankAccount::factory()->for($user)->create();

        $category = MoneyCategory::factory()->for($user)->create(['budget' => 100]);
        $category->transactions()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => now(),
            'amount' => -150, // Overspent by 50
            'description' => 'test',
        ]);

        Livewire::test(BudgetIndex::class)
            ->assertSet('rows.0.overspent', true)
            ->assertSet('isOverspent', true);
    }
}
