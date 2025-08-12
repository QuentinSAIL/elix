    <div class="flex flex-row flex-wrap gap-4">
        @if ($moneyDashboardPanels)
            @foreach ($moneyDashboardPanels as $moneyDashboardPanel)
                <div class="p-8 w-1/3 h-full bg-custom-accent flex flex-col">
                    <div class="ml-auto">
                        <span class="flex items-center justify-center space-x-2">
                            <flux:icon.trash class="cursor-pointer"
                                wire:click="deletePanel('{{ $moneyDashboardPanel->id }}')" variant="micro" role="button" tabindex="0" aria-label="{{ __('Delete panel') }}" />
                            <livewire:money.dashboard-panel-form wire:key="'panel-form-'.$moneyDashboardPanel->id"
                                :panel="$moneyDashboardPanel" :moneyDashboard="$moneyDashboard" />
                    </div>
                    <div>
                        <livewire:money.dashboard-panel wire:key="'panel-'.$moneyDashboardPanel->id" :panel="$moneyDashboardPanel"
                            :moneyDashboard="$moneyDashboard" />
                    </div>
                </div>
            @endforeach
        @endif
        <div
            class="p-8 w-1/3 h-full hover bg-custom-accent cursor-pointer flex flex-row items-center justify-center gap-2" role="button" tabindex="0" aria-label="{{ __('Add new panel') }}">
            <livewire:money.dashboard-panel-form wire:key="panel-form-create" :moneyDashboard="$moneyDashboard" />
        </div>
    </div>
