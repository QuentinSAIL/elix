  <div class="mx-auto max-w-screen-2xl px-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 justify-items-center"
           wire:ignore.self
           x-data="{
               panels: @js($moneyDashboardPanels->pluck('id')),
               sortable: null,
               init() {
                   this.initSortable()
                   Livewire.hook('message.processed', () => this.initSortable())
               },
               initSortable() {
                   if (this.sortable) this.sortable.destroy()
                   this.sortable = new Sortable(this.$refs.panelsContainer, {
                       handle: '.drag-handle',
                       animation: 150,
                       onEnd: evt => {
                           this.panels.splice(evt.newIndex, 0, this.panels.splice(evt.oldIndex, 1)[0])
                           this.$wire.updatePanelOrder(this.panels)
                       },
                   })
               },
           }">

          <div x-ref="panelsContainer" class="contents">
              @foreach ($moneyDashboardPanels as $moneyDashboardPanel)
                  <div class="w-full h-full bg-custom-accent rounded-xl p-6 flex flex-col"
                      wire:key="panel-wrap-{{ $moneyDashboardPanel->id }}"
                      data-panel-id="{{ $moneyDashboardPanel->id }}">
                      <div class="ml-auto flex items-center space-x-2">
                          <button type="button" class="drag-handle cursor-move text-gray-400 hover:text-gray-600"
                                  aria-label="{{ __('Reorder panel') }}">
                              <flux:icon.bars-4 variant="micro" aria-hidden="true" />
                          </button>
                          <flux:icon.document-duplicate class="cursor-pointer text-blue-500 hover:text-blue-700"
                              wire:click="duplicatePanel('{{ $moneyDashboardPanel->id }}')"
                              variant="micro" role="button" tabindex="0" aria-label="{{ __('Duplicate panel') }}" />
                          <flux:icon.trash class="cursor-pointer text-red-500 hover:text-red-700"
                              wire:click="deletePanel('{{ $moneyDashboardPanel->id }}')"
                              variant="micro" role="button" tabindex="0" aria-label="{{ __('Delete panel') }}" />
                          <livewire:money.dashboard-panel-form :panel="$moneyDashboardPanel" :moneyDashboard="$moneyDashboard"
                              wire:key="panel-form-{{ $moneyDashboardPanel->id }}" />
                      </div>

                      <div class="mt-4">
                          <livewire:money.dashboard-panel :panel="$moneyDashboardPanel" :moneyDashboard="$moneyDashboard"
                              wire:key="panel-{{ $moneyDashboardPanel->id }}" />
                      </div>
                  </div>
              @endforeach
          </div>

          @if ($moneyDashboardPanels->isEmpty())
              <div class="col-span-full text-center opacity-70 py-12">
                  {{ __('No panels yet') }}
              </div>
          @endif

          <div class="w-full h-full bg-custom-accent rounded-xl p-6 flex flex-row items-center justify-center gap-2 hover:opacity-80 cursor-pointer"
              role="button" tabindex="0" aria-label="{{ __('Add new panel') }}">
              <livewire:money.dashboard-panel-form wire:key="panel-form-create" :moneyDashboard="$moneyDashboard" />
          </div>
      </div>
  </div>
