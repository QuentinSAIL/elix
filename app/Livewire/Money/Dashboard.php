<?php

namespace App\Livewire\Money;

use App\Models\MoneyDashboardPanel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Dashboard extends Component
{
    public $user;

    public ?\App\Models\MoneyDashboard $moneyDashboard = null;

    public $moneyDashboardPanels;

    public function mount()
    {
        $this->user = Auth::user();
        /** @phpstan-ignore-next-line */
        $this->moneyDashboard = $this->user->moneyDashboards()->first() ?? (/** @var \App\Models\MoneyDashboard */ $this->user->moneyDashboards()->create());
        $this->moneyDashboardPanels = $this->moneyDashboard->panels()->orderBy('order')->get();
    }

    public function deletePanel($panelId)
    {
        $panel = $this->moneyDashboardPanels->find($panelId);
        if ($panel) {
            $panel->delete();
            $this->moneyDashboardPanels = $this->moneyDashboard->panels()->orderBy('order')->get();
            Toaster::success(__('Panel deleted successfully'));
        } else {
            Toaster::error(__('Panel not found'));
        }
    }

    public function duplicatePanel($panelId)
    {
        $originalPanel = $this->moneyDashboardPanels->find($panelId);
        if ($originalPanel) {
            // Get the next order number
            $maxOrder = $this->moneyDashboard->panels()->max('order') ?? 0;

            // Create new panel with same data
            $newPanel = MoneyDashboardPanel::create([
                'money_dashboard_id' => $originalPanel->money_dashboard_id,
                'title' => $originalPanel->title . ' (Copy)',
                'type' => $originalPanel->type,
                'period_type' => $originalPanel->period_type,
                'order' => $maxOrder + 1,
            ]);

            // Copy relationships
            $newPanel->bankAccounts()->sync($originalPanel->bankAccounts->pluck('id'));
            $newPanel->categories()->sync($originalPanel->categories->pluck('id'));

            $this->moneyDashboardPanels = $this->moneyDashboard->panels()->orderBy('order')->get();
            Toaster::success(__('Panel duplicated successfully'));
        } else {
            Toaster::error(__('Panel not found'));
        }
    }

    public function updatePanelOrder($panelIds)
    {
        try {
            // Validate that all panel IDs exist and belong to this dashboard
            $validPanelIds = $this->moneyDashboard->panels()->pluck('id')->toArray();
            $filteredPanelIds = array_filter($panelIds, function($id) use ($validPanelIds) {
                return in_array($id, $validPanelIds);
            });

            if (count($filteredPanelIds) !== count($panelIds)) {
                throw new \Exception('Invalid panel IDs provided');
            }

            // Update order in a transaction
            \DB::transaction(function() use ($filteredPanelIds) {
                foreach ($filteredPanelIds as $index => $panelId) {
                    MoneyDashboardPanel::where('id', $panelId)
                        ->where('money_dashboard_id', $this->moneyDashboard->id)
                        ->update(['order' => $index + 1]);
                }
            });

            // Refresh the panels collection
            $this->moneyDashboardPanels = $this->moneyDashboard->panels()->orderBy('order')->get();

            // Only show success message if not in a drag operation
            if (!request()->hasHeader('X-Livewire')) {
                Toaster::success(__('Panel order updated successfully'));
            }
        } catch (\Exception $e) {
            // Refresh panels to original state
            $this->moneyDashboardPanels = $this->moneyDashboard->panels()->orderBy('order')->get();

            if (!request()->hasHeader('X-Livewire')) {
                Toaster::error(__('Failed to update panel order'));
            }
        }
    }

    public function render()
    {
        return view('livewire.money.dashboard');
    }
}
