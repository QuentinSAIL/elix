<?php

namespace App\Livewire\Money;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class DashboardPanel extends Component
{
    public $user;
    public $moneyDashboardPanel;
    public $bankAccounts;
    public $categories;

    public $transactions;
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->user = Auth::user();

        // TODO: REMOVE THIS LATER
        $moneyDashboard = $this->user->moneyDashboards()->first();
        $this->moneyDashboardPanel = $moneyDashboard->panels()->first();
        $this->bankAccounts = $this->user->bankAccounts()->pluck('id')->toArray();
        $this->categories = $this->user->moneyCategories()->pluck('id')->toArray();
        //

        $this->assignDateRange();
        $this->transactions = $this->getTransactions();
        dd($this->transactions);
    }

    public function assignDateRange()
    {
        $period = $this->moneyDashboardPanel->determinePeriode();
        $this->startDate = $period['startDate']->format('Y-m-d');
        $this->endDate = $period['endDate']->format('Y-m-d');
    }

    public function getTransactions()
    {
        return $this->moneyDashboardPanel->getTransactions($this->startDate, $this->endDate, [
            'accounts' => $this->bankAccounts,
            'categories' => $this->categories,
        ]);
    }

    public function render()
    {
        return view('livewire.money.dashboard-panel');
    }
}
