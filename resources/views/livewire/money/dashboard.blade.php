    <div class="flex flex-row flex-wrap gap-4">
        @if ($moneyDashboardPanels)
            @foreach ($moneyDashboardPanels as $moneyDashboardPanel)
                <div class="p-8 w-1/3 h-full bg-custom-accent cursor-pointer">
                    <livewire:money.dashboard-panel :moneyDashboardPanel="$moneyDashboardPanel" />
                </div>
            @endforeach
            <div class="p-8 w-1/3 h-full bg-custom-accent cursor-pointer"><span>Ajouter un panneau <flux:icon.plus /></span></div>    @endif
    </div>
