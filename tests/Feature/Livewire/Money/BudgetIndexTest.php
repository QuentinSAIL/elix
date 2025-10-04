<?php

namespace Tests\Feature\Livewire\Money;

use App\Models\BankAccount;
use App\Livewire\Money\BudgetIndex;
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
            'description' => 'test'
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
            'description' => 'test'
        ]);

        Livewire::test(BudgetIndex::class)
            ->set('month', now()->subMonth()->format('Y-m'))
            ->assertSet('rows.0.spent', -50.0)
            ->set('month', now()->format('Y-m'))
            ->assertSet('rows.0.spent', 0.0);
    }
}
