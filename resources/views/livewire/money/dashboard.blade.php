{{-- Wrapper plein écran centré --}}
<div class="w-full">
  <div class="mx-auto max-w-screen-2xl px-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 justify-items-center">
      @foreach ($moneyDashboard->panels as $moneyDashboardPanel)
        <div class="w-full h-full bg-custom-accent rounded-xl p-6 flex flex-col"
             wire:key="panel-wrap-{{ $moneyDashboardPanel->id }}">
          <div class="ml-auto flex items-center space-x-2">
            <flux:icon.trash class="cursor-pointer"
              wire:click="deletePanel('{{ $moneyDashboardPanel->id }}')"
              variant="micro" role="button" tabindex="0"
              aria-label="{{ __('Delete panel') }}" />
            <livewire:money.dashboard-panel-form
              :panel="$moneyDashboardPanel"
              :moneyDashboard="$moneyDashboard"
              wire:key="panel-form-{{ $moneyDashboardPanel->id }}" />
          </div>

          <div class="mt-4">
            <livewire:money.dashboard-panel
              :panel="$moneyDashboardPanel"
              :moneyDashboard="$moneyDashboard"
              wire:key="panel-{{ $moneyDashboardPanel->id }}" />
          </div>
        </div>
      @endforeach

      @if ($moneyDashboard->panels->isEmpty())
        <div class="col-span-full text-center opacity-70 py-12">
          {{ __('No panels yet') }}
        </div>
      @endif
    </div>
  </div>
</div>
