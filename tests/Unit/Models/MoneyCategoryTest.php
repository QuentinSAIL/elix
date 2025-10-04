<?php

namespace Tests\Unit\Models;

use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \App\Models\MoneyCategory
 */
class MoneyCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    #[test]
    public function it_has_many_transactions()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        BankTransactions::factory()->count(3)->create(['money_category_id' => $category->id, 'user_id' => $this->user->id]);

        $this->assertInstanceOf(Collection::class, $category->transactions);
        $this->assertCount(3, $category->transactions);
    }

    #[test]
    public function it_has_many_category_matches()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->count(2)->create(['money_category_id' => $category->id, 'user_id' => $this->user->id]);

        $this->assertInstanceOf(Collection::class, $category->categoryMatches);
        $this->assertCount(2, $category->categoryMatches);
    }

    #[test]
    public function it_belongs_to_a_user()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(User::class, $category->user);
        $this->assertEquals($this->user->id, $category->user->id);
    }

    #[test]
    public function it_has_one_wallet()
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'wallet_id' => $wallet->id]);

        $this->assertInstanceOf(Wallet::class, $category->wallet);
        $this->assertEquals($wallet->id, $category->wallet->id);
    }

    #[test]
    public function spent_for_month_calculates_expenses_correctly()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        $month = Carbon::now();

        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => -50, 'transaction_date' => $month->format('Y-m-d')]);
        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => -75, 'transaction_date' => $month->format('Y-m-d')]);
        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => 100, 'transaction_date' => $month->format('Y-m-d')]); // Income, should be ignored
        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => -20, 'transaction_date' => $month->subMonth()->format('Y-m-d')]); // Previous month, should be ignored

        $spent = $category->spentForMonth($month);

        $this->assertEquals(-125.0, $spent);
    }

    #[test]
    public function spent_for_month_returns_zero_if_no_expenses()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        $month = Carbon::now();

        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => 100, 'transaction_date' => $month->format('Y-m-d')]);

        $spent = $category->spentForMonth($month);

        $this->assertEquals(0.0, $spent);
    }

    #[test]
    public function remaining_for_month_returns_null_if_no_budget()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'budget' => null]);
        $month = Carbon::now();

        $remaining = $category->remainingForMonth($month);

        $this->assertNull($remaining);
    }

    #[test]
    public function remaining_for_month_calculates_correctly()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'budget' => 200]);
        $month = Carbon::now();

        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => -50, 'transaction_date' => $month->format('Y-m-d')]);

        $remaining = $category->remainingForMonth($month);

        $this->assertEquals(150.0, $remaining); // 200 + (-50) = 150
    }

    #[test]
    public function is_overspent_for_month_returns_true_if_overspent()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'budget' => 100]);
        $month = Carbon::now();

        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => -150, 'transaction_date' => $month->format('Y-m-d')]);

        $this->assertTrue($category->isOverspentForMonth($month));
    }

    #[test]
    public function is_overspent_for_month_returns_false_if_not_overspent()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'budget' => 200]);
        $month = Carbon::now();

        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => -150, 'transaction_date' => $month->format('Y-m-d')]);

        $this->assertFalse($category->isOverspentForMonth($month));
    }

    #[test]
    public function is_overspent_for_month_returns_false_if_no_budget()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'budget' => null]);
        $month = Carbon::now();

        BankTransactions::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'amount' => -150, 'transaction_date' => $month->format('Y-m-d')]);

        $this->assertFalse($category->isOverspentForMonth($month));
    }
}