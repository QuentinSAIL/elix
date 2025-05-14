    <div class="flex flex-row gap-4">
        @if ($moneyDashboardPanels)
            @foreach ($moneyDashboardPanels as $moneyDashboardPanel)
                <div class="p-8 h-full bg-custom-accent cursor-pointer">
                    <div>
                        <livewire:money.dashboard-panel :moneyDashboardPanel="$moneyDashboardPanel" />
                    </div>
                </div>
            @endforeach
        @endif
    </div>
