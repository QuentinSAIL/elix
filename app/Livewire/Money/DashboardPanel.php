<?php

namespace App\Livewire\Money;

use App\Services\DashboardService;
use App\Http\Livewire\Traits\Notifies;
use App\Models\MoneyCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPanel extends Component
{
    use Notifies;

    public $user;

    public $title;

    public $isExpensePanel = true;

    public $displayUncategorized = false;

    public $panel;

    public $bankAccounts;

    public $categories;

    public $transactions;

    public $startDate;

    public $endDate;

    public $labels = [];

    public $values = [];

    public $colors = [];

    public function mount(DashboardService $dashboardService)
    {
        $this->user = Auth::user();
        $this->title = $this->panel?->title ?? 'Dashboard Panel';

        $data = $dashboardService->getPanelData($this->panel, $this->isExpensePanel, $this->displayUncategorized);

        $this->labels = $data['labels'];
        $this->values = $data['values'];
        $this->colors = $data['colors'];
    }

    

    public function edit()
    {
        $this->notifyInfo('Editer');
    }

    public function render()
    {
        return view('livewire.money.dashboard-panel');
    }
}
