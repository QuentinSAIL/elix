<?php

namespace Tests\Feature\Models;

use App\Models\MoneyCategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_category_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $category->user);
        $this->assertEquals($user->id, $category->user->id);
    }

    public function test_money_category_has_many_wallets(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create(['user_id' => $user->id]);
        Wallet::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_linked_id' => $category->id,
        ]);

        $this->assertInstanceOf(Wallet::class, $category->wallet);
    }

    public function test_money_category_can_be_created(): void
    {
        $user = User::factory()->create();

        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Category',
            'budget' => '5000.00',
        ]);

        $this->assertDatabaseHas('money_categories', [
            'id' => $category->id,
            'user_id' => $user->id,
            'name' => 'Test Category',
            'budget' => '5000.00',
        ]);
    }

    public function test_money_category_has_correct_fillable_attributes(): void
    {
        $category = new MoneyCategory;

        $expectedFillable = [
            'id',
            'user_id',
            'name',
            'description',
            'color',
            'budget',
            'include_in_dashboard',
            'user_id',
            'created_at',
            'updated_at',
        ];

        $this->assertEquals($expectedFillable, $category->getFillable());
    }

    public function test_money_category_has_correct_casts(): void
    {
        $category = new MoneyCategory;

        $expectedCasts = [];

        $this->assertEquals($expectedCasts, $category->getCasts());
    }

    public function test_money_category_can_get_spent_for_month(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create(['user_id' => $user->id]);

        // Create some transactions for this month
        $bankAccount = \App\Models\BankAccount::factory()->create(['user_id' => $user->id]);
        \App\Models\BankTransactions::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'money_category_id' => $category->id,
            'amount' => -100.0,
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $spent = $category->spentForMonth(now());

        $this->assertEquals(-100.0, $spent);
    }

    public function test_money_category_can_get_remaining_for_month(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'budget' => '1000.00',
        ]);

        // Create some transactions for this month
        $bankAccount = \App\Models\BankAccount::factory()->create(['user_id' => $user->id]);
        \App\Models\BankTransactions::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'money_category_id' => $category->id,
            'amount' => -200.0,
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $remaining = $category->remainingForMonth(now());

        $this->assertEquals(800.0, $remaining);
    }

    public function test_money_category_can_check_if_overspent_for_month(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'budget' => '500.00',
        ]);

        // Create transactions that exceed budget
        $bankAccount = \App\Models\BankAccount::factory()->create(['user_id' => $user->id]);
        \App\Models\BankTransactions::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'money_category_id' => $category->id,
            'amount' => -600.0,
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $this->assertTrue($category->isOverspentForMonth(now()));
    }

    public function test_money_category_can_check_if_not_overspent_for_month(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'budget' => '1000.00',
        ]);

        // Create transactions within budget
        $bankAccount = \App\Models\BankAccount::factory()->create(['user_id' => $user->id]);
        \App\Models\BankTransactions::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'money_category_id' => $category->id,
            'amount' => -200.0,
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $this->assertFalse($category->isOverspentForMonth(now()));
    }
}
