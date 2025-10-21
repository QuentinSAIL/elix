<div class="min-h-screen bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800" wire:key="wallet-index-main">
    <!-- Header Section -->
    <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border-b border-zinc-200/50 dark:border-zinc-700/50 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-zinc-50 tracking-tight">{{ __('Wallets') }}</h1>
                    <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 mt-1 sm:mt-2">{{ __('Manage your virtual wallets and track your assets') }}</p>
                    @if(!$wallets->isEmpty())
                        <div class="mt-3">
                            <div class="inline-flex items-center px-3 sm:px-4 py-2 rounded-xl bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primarydark-900/20 dark:to-primarydark-800/20 border border-color">
                                <flux:icon.banknotes class="w-4 h-4 sm:w-5 sm:h-5 text-color mr-2" />
                                <span class="text-xs sm:text-sm font-semibold">
                                    {{ __('Total Portfolio Value') }}: {{ number_format($this->getTotalPortfolioValue(), 2) }}{{ $this->getCurrencySymbol() }}
                                </span>
                            </div>
                            @if($this->hasMultipleCurrencies())
                                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Converted to your preferred currency') }} ({{ $this->userCurrency }})
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="flex items-center justify-end sm:justify-start">
                    <flux:modal.trigger name="create-wallet">
                        <flux:button class="cursor-pointer w-full sm:w-auto" variant="primary" icon="plus" as="button">{{ __('New wallet') }}</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <!-- Create Wallet Modal -->
        <flux:modal name="create-wallet" class="w-11/12 sm:w-5/6 lg:w-1/2 max-w-none mx-auto">
            <livewire:money.wallet-form wire:key="wallet-form-create" />
        </flux:modal>

        @if ($wallets->isEmpty())
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700 rounded-2xl flex items-center justify-center mb-6">
                    <flux:icon.wallet class="w-12 h-12 text-zinc-400 dark:text-zinc-500" />
                </div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 mb-2">{{ __('No wallets yet') }}</h3>
                <p class="text-zinc-600 dark:text-zinc-400 mb-8 max-w-md">{{ __('Create your first wallet to start tracking your assets and balances') }}</p>
                <flux:modal.trigger name="create-wallet">
                    <flux:button class="cursor-pointer" variant="primary" icon="plus" as="button">{{ __('Create your first wallet') }}</flux:button>
                </flux:modal.trigger>
            </div>
        @else
            <!-- Wallets Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 lg:gap-8"
                 wire:ignore.self
                 x-data="{
                     wallets: @js($wallets->pluck('id')),
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
                             this.sortable = new Sortable(this.$refs.walletsContainer, {
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
                                         const movedItem = this.wallets.splice(evt.oldIndex, 1)[0]
                                         this.wallets.splice(evt.newIndex, 0, movedItem)

                                         // Update server with debounce
                                         clearTimeout(this.updateTimeout)
                                         this.updateTimeout = setTimeout(() => {
                                             this.$wire.updateWalletOrder(this.wallets).then(() => {
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

                <!-- Wallets Container -->
                <div x-ref="walletsContainer" class="contents">
                    @foreach ($wallets as $wallet)
                        <div class="group bg-white/70 dark:bg-zinc-800/70 backdrop-blur-sm rounded-xl sm:rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm hover:shadow-lg dark:hover:shadow-zinc-900/50 transition-all duration-300 overflow-hidden"
                             wire:key="wallet-{{ $wallet->id }}"
                             data-wallet-id="{{ $wallet->id }}">
                            <!-- Wallet Header -->
                            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-zinc-100/50 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50/50 dark:from-zinc-800/50 to-transparent">
                                <!-- Top row: Drag handle and Actions -->
                                <div class="flex items-center justify-between mb-2">
                                    <button type="button" class="drag-handle cursor-move text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-400 transition-colors p-1 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                            aria-label="{{ __('Reorder wallet') }}">
                                        <flux:icon.bars-4 variant="micro" aria-hidden="true" />
                                    </button>

                                    <!-- Actions Menu (Desktop only) -->
                                    <div class="hidden md:flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        @if($wallet->mode === 'multi' && $wallet->positions()->count() > 0)
                                            <flux:modal.trigger name="view-wallet-{{ $wallet->id }}">
                                                <button class="cursor-pointer p-2 text-zinc-400 dark:text-zinc-500 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-all duration-200"
                                                        aria-label="{{ __('View positions') }}">
                                                    <flux:icon.eye variant="micro" />
                                                </button>
                                            </flux:modal.trigger>
                                        @endif
                                        <flux:modal.trigger name="edit-wallet-{{ $wallet->id }}">
                                            <button class="cursor-pointer p-2 text-zinc-400 dark:text-zinc-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-200"
                                                    aria-label="{{ __('Edit wallet') }}">
                                                <flux:icon.pencil variant="micro" />
                                            </button>
                                        </flux:modal.trigger>
                                        <flux:modal.trigger name="delete-wallet-{{ $wallet->id }}">
                                            <button class="cursor-pointer p-2 text-zinc-400 dark:text-zinc-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200"
                                                    aria-label="{{ __('Delete wallet') }}">
                                                <flux:icon.trash variant="micro" />
                                            </button>
                                        </flux:modal.trigger>
                                    </div>
                                </div>

                                <!-- Bottom row: Wallet name and info -->
                                <div class="space-y-1">
                                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-50 tracking-tight break-words leading-tight"
                                        @if(strlen($wallet->name) > 30)
                                            title="{{ $wallet->name }}"
                                        @endif>
                                        {{ $wallet->name }}
                                    </h3>
                                    @php($positionsCount = $wallet->positions()->count())
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                        @if($wallet->mode === 'single')
                                            {{ __('Simple wallet') }}
                                        @else
                                            {{ $positionsCount }} {{ $positionsCount === 1 ? __('position') : __('positions') }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                        <!-- Wallet Content -->
                        <div class="p-4 sm:p-6">
                            <!-- Balance -->
                            <div class="text-center py-4 sm:py-6">
                                <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Balance') }}</div>
                                <div class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-primary-500 to-primary-600 dark:from-primarydark-500 dark:to-primarydark-400 bg-clip-text text-transparent mb-2 break-words">
                                    {{ number_format($this->getWalletBalanceInCurrency($wallet), 2) }}{{ $this->getCurrencySymbol() }}
                                </div>
                                @if($wallet->mode === 'multi' && $wallet->positions()->count() > 0)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Calculated from current market prices') }}</div>
                                @endif
                            </div>

                            <!-- Positions Preview (only for multi mode) -->
                            @if($wallet->mode === 'multi')
                                @php($positionsPreview = $this->getTopPositionsByValue($wallet, 3))
                                @if ($positionsPreview->isNotEmpty())
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Top positions by value') }}</div>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach ($positionsPreview as $pos)
                                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-2 px-3 rounded-lg bg-zinc-50/50 dark:bg-zinc-800/50 border border-zinc-200/50 dark:border-zinc-700/50 hover:bg-zinc-100/50 dark:hover:bg-zinc-700/50 transition-colors gap-1 sm:gap-0">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="font-medium text-sm break-words text-zinc-900 dark:text-zinc-50">{{ $pos->name }}</div>
                                                        @if ($pos->ticker)
                                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $pos->ticker }}</div>
                                                        @endif
                                                    </div>
                                                    <div class="text-left sm:text-right">
                                                        <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                                            {{ rtrim(rtrim($pos->quantity, '0'), '.') }} {{ $pos->unit }}
                                                        </div>
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ number_format($pos->getCurrentMarketValue($this->userCurrency), 2) }}{{ $this->getCurrencySymbol() }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-6">
                                        <div class="w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-3">
                                            <flux:icon.chart-bar class="w-6 h-6 text-zinc-400 dark:text-zinc-500" />
                                        </div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">{{ __('No positions yet') }}</div>
                                        <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Add positions to track your investments') }}</div>
                                    </div>
                                @endif
                            @else
                                <!-- Single mode info -->
                                <div class="text-center py-6">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/20 dark:to-green-800/20 flex items-center justify-center mx-auto mb-3">
                                        <flux:icon.check class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Simple wallet mode') }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ __('Balance managed directly') }}</div>
                                </div>
                            @endif
                        </div>

                        <!-- Mobile Actions (visible on mobile, hidden on desktop) -->
                        <div class="md:hidden px-4 sm:px-6 py-3 border-t border-zinc-100/50 dark:border-zinc-700/50 bg-zinc-50/50 dark:bg-zinc-800/50">
                            <div class="flex items-center justify-center space-x-4">
                                @if($wallet->mode === 'multi' && $wallet->positions()->count() > 0)
                                    <flux:modal.trigger name="view-wallet-{{ $wallet->id }}">
                                        <button class="cursor-pointer p-2 text-zinc-400 dark:text-zinc-500 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-all duration-200"
                                                aria-label="{{ __('View positions') }}">
                                            <flux:icon.eye variant="micro" />
                                        </button>
                                    </flux:modal.trigger>
                                @endif
                                <flux:modal.trigger name="edit-wallet-{{ $wallet->id }}">
                                    <button class="cursor-pointer p-2 text-zinc-400 dark:text-zinc-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-200"
                                            aria-label="{{ __('Edit wallet') }}">
                                        <flux:icon.pencil variant="micro" />
                                    </button>
                                </flux:modal.trigger>
                                <flux:modal.trigger name="delete-wallet-{{ $wallet->id }}">
                                    <button class="p-2 text-zinc-400 dark:text-zinc-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200"
                                            aria-label="{{ __('Delete wallet') }}">
                                        <flux:icon.trash variant="micro" />
                                    </button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>
            </div>

            <!-- Render modals outside the grid so they don't become grid items -->
            @foreach ($wallets as $wallet)
                <!-- Edit Wallet Modal -->
                <flux:modal name="edit-wallet-{{ $wallet->id }}" class="w-11/12 sm:w-5/6 lg:w-1/2 max-w-none mx-auto" wire:key="edit-modal-{{ $wallet->id }}">
                    <livewire:money.wallet-form :wallet="$wallet" wire:key="wallet-form-edit-{{ $wallet->id }}" />
                </flux:modal>

                <!-- Delete Confirmation Modal -->
                <flux:modal name="delete-wallet-{{ $wallet->id }}" class="w-11/12 sm:w-5/6 lg:w-2/3 max-w-none mx-auto" wire:key="delete-modal-{{ $wallet->id }}">
                    <div class="space-y-6">
                        <div class="text-center">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/20 dark:to-red-800/20 flex items-center justify-center mx-auto mb-4">
                                <flux:icon.trash class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 mb-2">{{ __('Delete wallet?') }}</h2>
                            <p class="text-zinc-600 dark:text-zinc-400">{{ __('This action cannot be undone. All positions in this wallet will be permanently deleted.') }}</p>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-end gap-3">
                            <flux:button class="cursor-pointer w-full sm:w-auto" variant="ghost" @click="Flux.modals().close('delete-wallet-{{ $wallet->id }}')">{{ __('Cancel') }}</flux:button>
                            <flux:button class="cursor-pointer w-full sm:w-auto" variant="danger" wire:click="delete('{{ $wallet->id }}')">{{ __('Delete wallet') }}</flux:button>
                        </div>
                    </div>
                </flux:modal>

                <!-- View Positions Modal -->
                @if($wallet->mode === 'multi' && $wallet->positions()->count() > 0)
                    <flux:modal name="view-wallet-{{ $wallet->id }}" class="w-11/12 sm:w-1/2 lg:w-2/5 xl:w-1/3 max-w-none mx-auto" wire:key="view-modal-{{ $wallet->id }}">
                        <div class="space-y-2 sm:space-y-4 lg:space-y-6">
                            <!-- Modal Header -->
                            <div class="mt-2 sm:mt-4 lg:mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4">
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-50 break-words leading-tight">{{ $wallet->name }}</h2>
                                    <p class="text-zinc-600 dark:text-zinc-400 mt-1">{{ __('Portfolio Positions') }}</p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Value') }}</div>
                                    <div class="text-lg sm:text-xl font-bold bg-gradient-to-r from-primary-500 to-primary-600 dark:from-primarydark-500 dark:to-primarydark-400 bg-clip-text text-transparent">
                                        {{ number_format($this->getWalletBalanceInCurrency($wallet), 2) }}{{ $this->getCurrencySymbol() }}
                                    </div>
                                </div>
                            </div>

                            <!-- Positions List -->
                            <div class="max-h-64 sm:max-h-80 lg:max-h-96 overflow-y-auto space-y-2 sm:space-y-3">
                                @php($allPositions = $this->getAllPositions($wallet))
                                @forelse($allPositions as $position)
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-2 sm:p-3 lg:p-4 rounded-lg bg-zinc-50/50 dark:bg-zinc-800/50 border border-zinc-200/50 dark:border-zinc-700/50 gap-1 sm:gap-2 lg:gap-0">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-medium text-sm text-zinc-900 dark:text-zinc-50 break-words">{{ $position->name }}</div>
                                            @if ($position->ticker)
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $position->ticker }} • {{ $position->unit }}</div>
                                            @else
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $position->unit }}</div>
                                            @endif
                                        </div>
                                        <div class="text-left sm:text-right">
                                            <div class="text-xs sm:text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ rtrim(rtrim($position->quantity, '0'), '.') }} {{ $position->unit }}
                                            </div>
                                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">
                                                {{ number_format($position->getCurrentMarketValue($this->userCurrency), 2) }}{{ $this->getCurrencySymbol() }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <div class="w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-3">
                                            <flux:icon.chart-bar class="w-6 h-6 text-zinc-400 dark:text-zinc-500" />
                                        </div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No positions found') }}</div>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 pt-2 sm:pt-4 border-t border-zinc-200/50 dark:border-zinc-700/50">
                                <flux:button class="cursor-pointer w-full sm:w-auto" variant="ghost" @click="Flux.modals().close('view-wallet-{{ $wallet->id }}')">{{ __('Close') }}</flux:button>
                                <flux:modal.trigger name="edit-wallet-{{ $wallet->id }}">
                                    <flux:button class="cursor-pointer w-full sm:w-auto" variant="primary" @click="Flux.modals().close('view-wallet-{{ $wallet->id }}')">{{ __('Manage Positions') }}</flux:button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    </flux:modal>
                @endif
            @endforeach
        @endif
    </div>
</div>
