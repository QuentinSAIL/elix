<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoneyDashboardPanel extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = ['money_dashboard_id', 'type', 'periode_type', 'period_start', 'period_end'];

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
        return $this->belongsToMany(MoneyCategory::class, 'money_dashboard_panel_categories', 'money_dashboard_panel_id', 'category_id');
    }

    public function determinePeriode()
    {
        switch ($this->periode_type) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today();
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday();
                break;
            case 'weekly':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'monthly':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'yearly':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'dates':
                $startDate = Carbon::parse($this->moneyDashboardPanel->period_start);
                $endDate = Carbon::parse($this->moneyDashboardPanel->period_end);
                break;
        }
        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    public function getTransactions($startDate, $endDate, $filters = ['accounts' => [], 'categories' => []])
    {
        if (empty($startDate) || empty($endDate)) {
            return [];
        }

        $query = BankTransactions::whereBetween('transaction_date', [$startDate, $endDate]);

        if (!empty($filters["accounts"])) {
            $query->whereIn('bank_account_id', $filters["accounts"]);
        }

        if (!empty($filters["categories"])) {
            $query->whereIn('money_category_id', $filters["categories"]);
        }

        return $query->get();
    }
}
