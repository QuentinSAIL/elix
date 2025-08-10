<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoneyDashboardPanel extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = ['money_dashboard_id', 'type', 'title', 'is_expense', 'period_type'];

    public function dashboard()
    {
        return $this->belongsTo(MoneyDashboard::class, 'money_dashboard_id');
    }

    public function bankAccounts()
    {
        return $this->belongsToMany(BankAccount::class, 'money_dashboard_panel_bank_accounts', 'money_dashboard_panel_id', 'bank_account_id');
    }

    public function categories()
    {
        return $this->belongsToMany(MoneyCategory::class, 'money_dashboard_panel_categories', 'money_dashboard_panel_id', 'money_category_id');
    }

    public function determinePeriode()
    {
        switch ($this->period_type) {
            case 'daily':
                $startDate = Carbon::today();
                $endDate = Carbon::today();
                break;
            case 'weekly':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'biweekly':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->addWeek()->endOfWeek();
                break;
            case 'monthly':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'quarterly':
                $startDate = Carbon::now()->startOfQuarter();
                $endDate = Carbon::now()->endOfQuarter();
                break;
            case 'biannual':
                $half = Carbon::now()->month <= 6 ? 1 : 2;
                $startDate = Carbon::create(Carbon::now()->year, $half == 1 ? 1 : 7, 1)->startOfDay();
                $endDate = Carbon::create(Carbon::now()->year, $half == 1 ? 6 : 12, $half == 1 ? 30 : 31)->endOfDay();
                break;
            case 'yearly':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'all':
                $startDate = null;
                $endDate = null;
                break;
            default:
                $startDate = null;
                $endDate = null;
                break;
        }
        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    public function getTransactions($startDate, $endDate, $filters = ['accounts' => [], 'categories' => []])
    {
        $query = BankTransactions::query();

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        if (!empty($filters['accounts'])) {
            $query->whereIn('bank_account_id', $filters['accounts']);
        }

        if (!empty($filters['categories'])) {
            $query->whereIn('money_category_id', $filters['categories']);
        }

        return $query->with('category')->get();
    }
}
