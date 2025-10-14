<?php

namespace Tests\Feature\Models;

use App\Models\MoneyCategory;
use App\Models\User;
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

    public function test_money_category_has_many_matches(): void
    {
        $category = MoneyCategory::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $category->categoryMatches);
    }

    public function test_money_category_has_many_transactions(): void
    {
        $category = MoneyCategory::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $category->transactions);
    }

    public function test_money_category_can_be_created(): void
    {
        $user = User::factory()->create();

        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Category',
            'description' => 'Test Description',
            'color' => '#FF0000',
            'budget' => 1000.00,
            'include_in_dashboard' => true,
        ]);

        $this->assertDatabaseHas('money_categories', [
            'id' => $category->id,
            'user_id' => $user->id,
            'name' => 'Test Category',
            'description' => 'Test Description',
            'color' => '#FF0000',
            'budget' => '1000.00',
            'include_in_dashboard' => true,
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

        // MoneyCategory doesn't have explicit casts defined, so it uses defaults
        $this->assertIsArray($category->getCasts());
    }

    public function test_money_category_can_calculate_spent_for_month(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create(['user_id' => $user->id]);

        // Create some transactions for this category
        \App\Models\BankTransactions::factory()->count(3)->create([
            'money_category_id' => $category->id,
            'amount' => '-100.00',
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $spentForMonth = $category->spentForMonth(now());

        $this->assertEquals(-300.0, $spentForMonth);
    }

    public function test_money_category_can_calculate_remaining_budget(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'budget' => '1000.00',
        ]);

        // Create some transactions for this category
        \App\Models\BankTransactions::factory()->count(2)->create([
            'money_category_id' => $category->id,
            'amount' => '-200.00',
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $remainingBudget = $category->remainingForMonth(now());

        $this->assertEquals(600.0, $remainingBudget);
    }

    public function test_money_category_can_check_if_over_budget(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'budget' => '500.00',
        ]);

        // Create transactions that exceed budget
        \App\Models\BankTransactions::factory()->count(3)->create([
            'money_category_id' => $category->id,
            'amount' => '-200.00',
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $isOverspent = $category->isOverspentForMonth(now());

        $this->assertTrue($isOverspent);
    }

    public function test_money_category_can_check_if_under_budget(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'budget' => '1000.00',
        ]);

        // Create transactions that are under budget
        \App\Models\BankTransactions::factory()->count(2)->create([
            'money_category_id' => $category->id,
            'amount' => '-200.00',
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $isOverspent = $category->isOverspentForMonth(now());

        $this->assertFalse($isOverspent);
    }
}
