<div class="min-h-screen bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800">
    <!-- Header Section -->
    <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border-b border-zinc-200/50 dark:border-zinc-700/50 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-50 tracking-tight">{{ __('Financial Dashboard') }}</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-2">{{ __('Monitor your expenses and income with beautiful visualizations') }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="text-right">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Panels') }}</div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">{{ $moneyDashboardPanels->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-8"
             wire:ignore.self
             x-data="{
                 panels: @js($moneyDashboardPanels->pluck('id')),
                 sortable: null,
                 isUpdating: false,
                 init() {
                     this.initSortable()
                     Livewire.hook('message.processed', () => {
                         if (!this.isUpdating) {
                             this.initSortable()
                         }
                     })
                 },
                 initSortable() {
                     if (this.sortable) {
                         this.sortable.destroy()
                         this.sortable = null
                     }

                     // Wait for DOM to be ready
                     this.$nextTick(() => {
                         this.sortable = new Sortable(this.$refs.panelsContainer, {
                             handle: '.drag-handle',
                             animation: 200,
                             ghostClass: 'opacity-50',
                             chosenClass: 'scale-105',
                             forceFallback: true,
                             onStart: () => {
                                 this.isUpdating = true
                             },
                             onEnd: evt => {
                                 if (evt.oldIndex !== evt.newIndex) {
                                     // Update local array
                                     const movedItem = this.panels.splice(evt.oldIndex, 1)[0]
                                     this.panels.splice(evt.newIndex, 0, movedItem)

                                     // Update server with debounce
                                     clearTimeout(this.updateTimeout)
                                     this.updateTimeout = setTimeout(() => {
                                         this.$wire.updatePanelOrder(this.panels).then(() => {
                                             this.isUpdating = false
                                         }).catch(() => {
                                             this.isUpdating = false
                                         })
                                     }, 100)
                                 } else {
                                     this.isUpdating = false
                                 }
                             }
                         })
                     })
                 },
             }">

            <!-- Panels Container -->
            <div x-ref="panelsContainer" class="contents">
                @foreach ($moneyDashboardPanels as $moneyDashboardPanel)
                    <div class="group bg-white/70 dark:bg-zinc-800/70 backdrop-blur-sm rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm hover:shadow-lg dark:hover:shadow-zinc-900/50 transition-all duration-300 overflow-hidden"
                        wire:key="panel-wrap-{{ $moneyDashboardPanel->id }}"
                        data-panel-id="{{ $moneyDashboardPanel->id }}">

                        <!-- Panel Header -->
                        <div class="px-6 py-4 border-b border-zinc-100/50 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50/50 dark:from-zinc-800/50 to-transparent">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <button type="button" class="drag-handle cursor-move text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-400 transition-colors p-1 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                            aria-label="{{ __('Reorder panel') }}">
                                        <flux:icon.bars-4 variant="micro" aria-hidden="true" />
                                    </button>
                                </div>

                                <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <button wire:click="duplicatePanel('{{ $moneyDashboardPanel->id }}')"
                                            class="p-2 text-zinc-400 dark:text-zinc-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-200"
                                            aria-label="{{ __('Duplicate panel') }}">
                                        <flux:icon.document-duplicate variant="micro" />
                                    </button>
                                    <button wire:click="deletePanel('{{ $moneyDashboardPanel->id }}')"
                                            class="p-2 text-zinc-400 dark:text-zinc-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200"
                                            aria-label="{{ __('Delete panel') }}">
                                        <flux:icon.trash variant="micro" />
                                    </button>
                                    <livewire:money.dashboard-panel-form :panel="$moneyDashboardPanel" :moneyDashboard="$moneyDashboard"
                                        wire:key="panel-form-{{ $moneyDashboardPanel->id }}" />
                                </div>
                            </div>
                        </div>

                        <!-- Panel Content -->
                        <div class="p-6">
                            <livewire:money.dashboard-panel :panel="$moneyDashboardPanel" :moneyDashboard="$moneyDashboard"
                                wire:key="panel-{{ $moneyDashboardPanel->id }}" />
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Empty State -->
            @if ($moneyDashboardPanels->isEmpty())
                <div class="col-span-full flex flex-col items-center justify-center py-24 text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700 rounded-2xl flex items-center justify-center mb-6">
                        <flux:icon.chart-bar class="w-12 h-12 text-zinc-400 dark:text-zinc-500" />
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 mb-2">{{ __('No panels yet') }}</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-8 max-w-md">{{ __('Create your first panel to start visualizing your financial data') }}</p>
                </div>
            @endif

            <!-- Add Panel Button -->
            <livewire:money.dashboard-panel-form wire:key="panel-form-create" :moneyDashboard="$moneyDashboard" />
        </div>
    </div>
</div>
