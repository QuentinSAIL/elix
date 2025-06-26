<?php

namespace App\Livewire\Money;

use App\Services\DashboardService;
use App\Http\Livewire\Traits\Notifies;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPanelForm extends Component
{
    use Notifies;

    public $user;

    public $moneyDashboard;

    public $panel;

    public $edition;

    public $bankAccounts;

    public $categories;

    // form
    public $title;

    public $type;

    public $periodType;

    public array $accountsId = [];

    public array $categoriesId = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->edition = $this->panel ? true : false;
        $this->bankAccounts = $this->user->bankAccounts()->get()->pluck('name', 'id')->toArray();
        $this->categories = $this->user->moneyCategories()->get()->pluck('name', 'id')->toArray();
        $this->populateForm();
    }

    public function resetForm()
    {
        $this->title = '';
        $this->type = '';
        $this->accountsId = [];
        $this->categoriesId = [];
        $this->periodType = '';
    }

    public function populateForm()
    {
        if ($this->edition) {
            $this->title = $this->panel->title;
            $this->type = $this->panel->type;
            $this->accountsId = $this->panel->bankAccounts->pluck('id')->toArray();
            $this->categoriesId = $this->panel->categories->pluck('id')->toArray();
            $this->periodType = $this->panel->period_type;
        } else {
            $this->resetForm();
        }
    }

    public function save(DashboardService $dashboardService)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:bar,doughnut,pie,line,table,number',
            'accountsId' => 'array',
            'accountsId.*' => 'exists:bank_accounts,id',
            'categoriesId' => 'array',
            'categoriesId.*' => 'exists:money_categories,id',
            'periodType' => 'required|string|in:daily,weekly,biweekly,monthly,quarterly,biannual,yearly,all',
        ];

        try {
            $this->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notifyError($e->getMessage());

            return;
        }

        $dashboardService->savePanel(
            [
                'title' => $this->title,
                'type' => $this->type,
                'periodType' => $this->periodType,
                'accountsId' => $this->accountsId,
                'categoriesId' => $this->categoriesId,
            ],
            $this->moneyDashboard,
            $this->panel
        );

        $this->populateForm();
        if ($this->edition) {
            $this->notifySuccess(__('Panel edited successfully.'));
            Flux::modals()->close('panel-form-'.$this->panel->id);
        } else {
            $this->notifySuccess(__('Panel created successfully.'));
            Flux::modals()->close('panel-form-create');
        }

        return redirect()->route('money.dashboard');
    }

    public function render()
    {
        return view('livewire.money.dashboard-panel-form');
    }
}
