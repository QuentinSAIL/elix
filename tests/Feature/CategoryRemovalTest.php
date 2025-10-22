<?php

namespace Tests\Feature;

use App\Livewire\Money\CategorySelect;
use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_remove_category_from_transaction()
    {
        // Create user and test data
        $user = User::factory()->create();
        $account = BankAccount::factory()->create(['user_id' => $user->id]);
        $category = MoneyCategory::factory()->create(['user_id' => $user->id, 'name' => 'Test Category']);
        $transaction = BankTransactions::factory()->create([
            'bank_account_id' => $account->id,
            'money_category_id' => $category->id,
        ]);

        // Verify transaction has category
        $this->assertNotNull($transaction->category);
        $this->assertEquals($category->id, $transaction->category->id);

        // Test the component
        Livewire::actingAs($user)
            ->test(CategorySelect::class, [
                'transaction' => $transaction,
                'category' => $category,
            ])
            ->call('removeCategory');

        // Refresh transaction from database
        $transaction->refresh();

        // Verify category was removed
        $this->assertNull($transaction->category);
        $this->assertNull($transaction->money_category_id);
    }

    public function test_cannot_remove_category_without_transaction()
    {
        $user = User::factory()->create();

        // Test the component without transaction
        Livewire::actingAs($user)
            ->test(CategorySelect::class)
            ->call('removeCategory');
    }

    public function test_remove_category_updates_ui_state()
    {
        // Create user and test data
        $user = User::factory()->create();
        $account = BankAccount::factory()->create(['user_id' => $user->id]);
        $category = MoneyCategory::factory()->create(['user_id' => $user->id, 'name' => 'Test Category']);
        $transaction = BankTransactions::factory()->create([
            'bank_account_id' => $account->id,
            'money_category_id' => $category->id,
        ]);

        // Test the component
        $component = Livewire::actingAs($user)
            ->test(CategorySelect::class, [
                'transaction' => $transaction,
                'category' => $category,
            ]);

        // Verify initial state
        $this->assertEquals($category->name, $component->get('selectedCategory'));
        $this->assertEquals($category, $component->get('category'));

        // Remove category
        $component->call('removeCategory');

        // Verify UI state was updated
        $this->assertNull($component->get('selectedCategory'));
        $this->assertNull($component->get('category'));
    }

    public function test_remove_category_dispatches_events()
    {
        // Create user and test data
        $user = User::factory()->create();
        $account = BankAccount::factory()->create(['user_id' => $user->id]);
        $category = MoneyCategory::factory()->create(['user_id' => $user->id, 'name' => 'Test Category']);
        $transaction = BankTransactions::factory()->create([
            'bank_account_id' => $account->id,
            'money_category_id' => $category->id,
        ]);

        // Test the component
        Livewire::actingAs($user)
            ->test(CategorySelect::class, [
                'transaction' => $transaction,
                'category' => $category,
            ])
            ->call('removeCategory');
    }
}
