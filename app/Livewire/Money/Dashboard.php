<?php

namespace App\Livewire\Money;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Dashboard extends Component
{
    public $user;

    public $moneyDashboard;

    public $moneyDashboardPanels;

    public function mount()
    {
        $this->user = Auth::user();
        $this->moneyDashboard = $this->user->moneyDashboards()->first();
        if (! $this->moneyDashboard) {
            $this->moneyDashboard = $this->user->moneyDashboards()->create();
        }
        $this->moneyDashboardPanels = $this->moneyDashboard?->panels()->get();
    }

    public function deletePanel($panelId)
    {
        $panel = $this->moneyDashboardPanels->find($panelId);
        if ($panel) {
            $panel->delete();
            $this->moneyDashboardPanels = $this->moneyDashboard?->panels()->get();
            Toaster::success('Panel deleted successfully.');
        } else {
            Toaster::error('Panel not found.');
        }
    }

    public function render()
    {
        return view('livewire.money.dashboard');
    }
}
