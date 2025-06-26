<?php

namespace App\Livewire\Money;

use App\Services\DashboardService;
use App\Http\Livewire\Traits\Notifies;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    use Notifies;

    public $user;

    public $moneyDashboard;

    public $moneyDashboardPanels;

    public function mount(DashboardService $dashboardService)
    {
        $this->user = Auth::user();
        $this->moneyDashboard = $dashboardService->createOrGetDashboard();
        $this->moneyDashboardPanels = $this->moneyDashboard?->panels()->get();
    }

    public function deletePanel($panelId, DashboardService $dashboardService)
    {
        if ($dashboardService->deletePanel($panelId)) {
            $this->moneyDashboardPanels = $this->moneyDashboard?->panels()->get();
            $this->notifySuccess('Panel deleted successfully.');
        } else {
            $this->notifyError('Panel not found.');
        }
    }

    public function render()
    {
        return view('livewire.money.dashboard');
    }
}
