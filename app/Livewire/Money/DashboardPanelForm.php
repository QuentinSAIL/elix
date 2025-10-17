<?php

namespace App\Livewire\Money;

use App\Models\MoneyDashboardPanel;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class DashboardPanelForm extends Component
{
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

    public function save()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:bar,doughnut,pie,line,table,number,gauge,trend,category_comparison',
            'accountsId' => 'array',
            'accountsId.*' => 'exists:bank_accounts,id',
            'categoriesId' => 'array',
            'categoriesId.*' => 'exists:money_categories,id',
            'periodType' => 'required|string|in:daily,weekly,biweekly,monthly,quarterly,biannual,yearly,actual_month,previous_month,two_months_ago,three_months_ago,all',
        ];

        try {
            $this->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Toaster::error($e->getMessage());

            return;
        }

        // Get the next order number for new panels
        $order = $this->panel ? $this->panel->order : ($this->moneyDashboard->panels()->max('order') ?? 0) + 1;

        $panel = MoneyDashboardPanel::updateOrCreate(
            [
                'id' => $this->panel ? $this->panel->id : null,
            ],
            [
                'money_dashboard_id' => $this->moneyDashboard->id,
                'title' => $this->title,
                'type' => $this->type,
                'period_type' => $this->periodType,
                'order' => $order,
            ],
        );
        $panel->bankAccounts()->sync($this->accountsId);
        $panel->categories()->sync($this->categoriesId);

        $this->populateForm();
        if ($this->edition) {
            Toaster::success(__('Panel edited successfully.'));
            Flux::modals()->close('panel-form-'.$this->panel->id);
        } else {
            Toaster::success(__('Panel created successfully.'));
            Flux::modals()->close('panel-form-create');
        }

        return redirect()->route('money.dashboard');
    }

    public function render()
    {
        return view('livewire.money.dashboard-panel-form');
    }
}
