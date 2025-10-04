<?php

namespace Tests\Unit\Models;

use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\MoneyDashboard;
use App\Models\MoneyDashboardPanel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \App\Models\MoneyDashboardPanel
 */
class MoneyDashboardPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MoneyDashboard $dashboard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->dashboard = MoneyDashboard::factory()->create(['user_id' => $this->user->id]);
    }

    #[test]
    public function it_belongs_to_a_money_dashboard()
    {
        $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $this->dashboard->id]);
        $this->assertInstanceOf(MoneyDashboard::class, $panel->dashboard);
        $this->assertEquals($this->dashboard->id, $panel->dashboard->id);
    }

    #[test]
    public function it_can_have_many_bank_accounts()
    {
        $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $this->dashboard->id]);
        $accounts = BankAccount::factory()->count(2)->create(['user_id' => $this->user->id]);
        $panel->bankAccounts()->attach($accounts->pluck('id'));

        $this->assertInstanceOf(Collection::class, $panel->bankAccounts);
        $this->assertCount(2, $panel->bankAccounts);
    }

    #[test]
    public function it_can_have_many_categories()
    {
        $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $this->dashboard->id]);
        $categories = MoneyCategory::factory()->count(2)->create(['user_id' => $this->user->id]);
        $panel->categories()->attach($categories->pluck('id'));

        $this->assertInstanceOf(Collection::class, $panel->categories);
        $this->assertCount(2, $panel->categories);
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_daily()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'daily']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::today()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::today()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_weekly()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'weekly']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->startOfWeek()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->endOfWeek()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_biweekly()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'biweekly']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->startOfWeek()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->addWeek()->endOfWeek()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_monthly()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'monthly']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->startOfMonth()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->endOfMonth()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_quarterly()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'quarterly']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->startOfQuarter()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->endOfQuarter()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_biannual_first_half()
    {
        Carbon::setTestNow(Carbon::create(2024, 3, 15)); // Set to first half of the year
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'biannual']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::create(2024, 1, 1)->startOfDay()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::create(2024, 6, 30)->endOfDay()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_biannual_second_half()
    {
        Carbon::setTestNow(Carbon::create(2024, 9, 15)); // Set to second half of the year
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'biannual']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::create(2024, 7, 1)->startOfDay()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::create(2024, 12, 31)->endOfDay()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_yearly()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'yearly']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->startOfYear()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->endOfYear()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_actual_month()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'actual_month']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->startOfMonth()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_previous_month()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'previous_month']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->subMonth()->startOfMonth()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->subMonth()->endOfMonth()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_two_months_ago()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'two_months_ago']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->subMonths(2)->startOfMonth()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->subMonths(2)->endOfMonth()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_correct_dates_for_three_months_ago()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'three_months_ago']);
        $period = $panel->determinePeriode();

        $this->assertEquals(Carbon::now()->subMonths(3)->startOfMonth()->toDateString(), $period['startDate']->toDateString());
        $this->assertEquals(Carbon::now()->subMonths(3)->endOfMonth()->toDateString(), $period['endDate']->toDateString());
    }

    #[test]
    public function determine_periode_returns_null_dates_for_all()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'all']);
        $period = $panel->determinePeriode();

        $this->assertNull($period['startDate']);
        $this->assertNull($period['endDate']);
    }

    #[test]
    public function determine_periode_returns_null_dates_for_default()
    {
        $panel = MoneyDashboardPanel::factory()->create(['period_type' => 'unsupported']);
        $period = $panel->determinePeriode();

        $this->assertNull($period['startDate']);
        $this->assertNull($period['endDate']);
    }

    #[test]
    public function get_transactions_retrieves_all_transactions_without_filters()
    {
        $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $this->dashboard->id]);
        BankTransactions::factory()->count(3)->create(['user_id' => $this->user->id]);

        $transactions = $panel->getTransactions(null, null);

        $this->assertCount(3, $transactions);
    }

    #[test]
    public function get_transactions_applies_date_filters()
    {
        $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $this->dashboard->id]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'transaction_date' => '2024-01-15']);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'transaction_date' => '2024-02-10']);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'transaction_date' => '2024-03-20']);

        $transactions = $panel->getTransactions('2024-02-01', '2024-02-29');

        $this->assertCount(1, $transactions);
        $this->assertEquals('2024-02-10', $transactions->first()->transaction_date);
    }

    #[test]
    public function get_transactions_applies_account_filters()
    {
        $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $this->dashboard->id]);
        $account1 = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $account2 = BankAccount::factory()->create(['user_id' => $this->user->id]);

        BankTransactions::factory()->create(['user_id' => $this->user->id, 'bank_account_id' => $account1->id]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'bank_account_id' => $account2->id]);

        $transactions = $panel->getTransactions(null, null, ['accounts' => [$account1->id]]);

        $this->assertCount(1, $transactions);
        $this->assertEquals($account1->id, $transactions->first()->bank_account_id);
    }

    #[test]
    public function get_transactions_applies_category_filters()
    {
        $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $this->dashboard->id]);
        $category1 = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        $category2 = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category1->id]);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'money_category_id' => $category2->id]);

        $transactions = $panel->getTransactions(null, null, ['categories' => [$category1->id]]);

        $this->assertCount(1, $transactions);
        $this->assertEquals($category1->id, $transactions->first()->money_category_id);
    }

    #[test]
    public function get_transactions_applies_multiple_filters_simultaneously()
    {
        $panel = MoneyDashboardPanel::factory()->create(['money_dashboard_id' => $this->dashboard->id]);
        $account = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        BankTransactions::factory()->create(['user_id' => $this->user->id, 'bank_account_id' => $account->id, 'money_category_id' => $category->id, 'transaction_date' => '2024-01-15']);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'bank_account_id' => BankAccount::factory()->create(['user_id' => $this->user->id])->id, 'money_category_id' => $category->id, 'transaction_date' => '2024-01-15']);
        BankTransactions::factory()->create(['user_id' => $this->user->id, 'bank_account_id' => $account->id, 'money_category_id' => MoneyCategory::factory()->create(['user_id' => $this->user->id])->id, 'transaction_date' => '2024-01-15']);

        $filters = [
            'accounts' => [$account->id],
            'categories' => [$category->id],
        ];

        $transactions = $panel->getTransactions('2024-01-01', '2024-01-31', $filters);

        $this->assertCount(1, $transactions);
        $this->assertEquals($account->id, $transactions->first()->bank_account_id);
        $this->assertEquals($category->id, $transactions->first()->money_category_id);
        $this->assertEquals('2024-01-15', $transactions->first()->transaction_date);
    }
}
