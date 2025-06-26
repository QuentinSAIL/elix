<?php

namespace App\Services;

use App\Models\MoneyCategory;
use App\Models\MoneyDashboard;
use App\Models\MoneyDashboardPanel;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function createOrGetDashboard()
    {
        $user = Auth::user();
        $moneyDashboard = $user->moneyDashboards()->first();
        if (! $moneyDashboard) {
            $moneyDashboard = $user->moneyDashboards()->create();
        }

        return $moneyDashboard;
    }

    public function deletePanel($panelId)
    {
        $panel = MoneyDashboardPanel::find($panelId);
        if ($panel) {
            $panel->delete();

            return true;
        }

        return false;
    }

    public function getPanelData(MoneyDashboardPanel $panel, bool $isExpensePanel, bool $displayUncategorized)
    {
        $categories = $panel->categories()->get()->pluck('id')->toArray();
        $bankAccounts = $panel->bankAccounts()->get()->pluck('id')->toArray();

        $period = $panel->determinePeriode();
        $startDate = $period['startDate'] ? $period['startDate']->format('Y-m-d') : null;
        $endDate = $period['endDate'] ? $period['endDate']->format('Y-m-d') : null;

        $transactions = $panel->getTransactions($startDate, $endDate, [
            'accounts' => $bankAccounts,
            'categories' => $categories,
        ]);

        $filteredTransactions = $transactions->filter(function ($transaction) use ($isExpensePanel, $displayUncategorized) {
            $amountCondition = $isExpensePanel
            ? (float) $transaction->amount < 0
            : (float) $transaction->amount > 0;

            if (! $displayUncategorized && ! $transaction->category) {
                return false;
            }

            return $amountCondition;
        });

        $labels = $filteredTransactions
            ->map(function ($transaction) {
                return $transaction->category ? $transaction->category->name : 'Uncategorized';
            })
            ->unique()
            ->values()
            ->toArray();

        $values = $filteredTransactions
            ->groupBy(function ($transaction) {
                return $transaction->category ? $transaction->category->name : 'Uncategorized';
            })
            ->map(function ($group) {
                return $group->sum('amount');
            })
            ->values()
            ->toArray();

        $colors = [];
        foreach ($labels as $label) {
            $category = MoneyCategory::where('name', $label)->first();
            $colors[] = $category ? $category->color : '#CCCCCC';
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
        ];
    }

    public function savePanel(array $data, $moneyDashboard, $panel = null)
    {
        $panel = MoneyDashboardPanel::updateOrCreate(
            [
                'id' => $panel ? $panel->id : null,
            ],
            [
                'money_dashboard_id' => $moneyDashboard->id,
                'title' => $data['title'],
                'type' => $data['type'],
                'period_type' => $data['periodType'],
            ],
        );
        $panel->bankAccounts()->sync($data['accountsId']);
        $panel->categories()->sync($data['categoriesId']);

        return $panel;
    }
}
