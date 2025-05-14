<?php

namespace App\Livewire\Money;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $user;
    public $moneyDashboards;
    public $moneyDashboardPanels;

    public function mount()
    {
        $this->user = Auth::user();
        $this->moneyDashboards = $this->user->moneyDashboards()->first();
        $this->moneyDashboardPanels = $this->moneyDashboards?->panels()->get();
    }

    public function render()
    {
        return view('livewire.money.dashboard');
    }
}
