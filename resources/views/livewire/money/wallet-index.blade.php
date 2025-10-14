<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="2xl">{{ __('Wallets') }}</flux:heading>
            <flux:text class="text-grey-inverse mt-1">{{ __('Manage your virtual wallets and track your assets') }}</flux:text>
            @if(!$wallets->isEmpty())
                <div class="mt-2">
                    <flux:text class="text-sm font-medium text-grey-inverse">
                        {{ __('Total Portfolio Value') }}: {{ $this->getCurrencySymbol() }}{{ number_format($this->getTotalPortfolioValue(), 2) }}
                    </flux:text>
                </div>
            @endif
        </div>
        <flux:modal.trigger name="create-wallet">
            <flux:button variant="primary" icon="plus" as="button">{{ __('New wallet') }}</flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Create Wallet Modal -->
    <flux:modal name="create-wallet" class="!w-1/2 !max-w-none mx-auto">
        <livewire:money.wallet-form />
    </flux:modal>

    @if ($wallets->isEmpty())
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-20 rounded-2xl bg-custom-ultra border border-custom-accent">
            <div class="w-16 h-16 rounded-full bg-custom-accent/10 flex items-center justify-center mb-4">
                <flux:icon.wallet class="w-8 h-8 text-custom-accent" />
            </div>
            <flux:heading size="lg" class="mb-2">{{ __('No wallets yet') }}</flux:heading>
            <flux:text class="text-grey-inverse mb-6 text-center max-w-md">{{ __('Create your first wallet to start tracking your assets and balances') }}</flux:text>
            <flux:modal.trigger name="create-wallet">
                <flux:button variant="primary" icon="plus" as="button">{{ __('Create your first wallet') }}</flux:button>
            </flux:modal.trigger>
        </div>
    @else
        <!-- Wallets Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
            @foreach ($wallets as $wallet)
                <div class="group rounded-2xl bg-custom-ultra border border-custom-accent p-6 hover:border-custom-accent/50 transition-all duration-200 hover:shadow-lg flex flex-col">
                    <!-- Wallet Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <flux:heading size="lg" class="truncate">{{ $wallet->name }}</flux:heading>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-custom-accent/10 text-custom-accent border border-custom-accent/20">
                                    {{ $wallet->unit }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $wallet->mode === 'single' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                                    {{ $wallet->mode === 'single' ? __('Single') : __('Multi') }}
                                </span>
                            </div>
                            @php($positionsCount = $wallet->positions()->count())
                            <div class="text-sm text-grey-inverse">
                                @if($wallet->mode === 'single')
                                    {{ __('Simple wallet') }}
                                @else
                                    {{ $positionsCount }} {{ $positionsCount === 1 ? __('position') : __('positions') }}
                                @endif
                            </div>
                        </div>

                        <!-- Actions Menu -->
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <flux:modal.trigger name="edit-wallet-{{ $wallet->id }}">
                                <flux:button size="sm" variant="ghost" icon="eye" as="button" title="{{ __('Edit wallet') }}" />
                            </flux:modal.trigger>
                            <flux:modal.trigger name="delete-wallet-{{ $wallet->id }}">
                                <flux:button size="sm" variant="ghost" icon="trash" as="button" title="{{ __('Delete wallet') }}" />
                            </flux:modal.trigger>
                        </div>
                    </div>

                    <!-- Balance -->
                    <div class="mb-6">
                        <div class="text-sm text-grey-inverse mb-1">{{ __('Balance') }}</div>
                        <div class="text-2xl font-bold">{{ $this->getCurrencySymbol() }}{{ number_format($this->getWalletBalanceInCurrency($wallet), 2) }}</div>
                        @if($wallet->mode === 'multi' && $wallet->positions()->count() > 0)
                            <div class="text-xs text-grey-inverse mt-1">{{ __('Calculated from current market prices') }}</div>
                        @elseif($wallet->mode === 'single')
                            <div class="text-xs text-grey-inverse mt-1">{{ __('Original balance') }}: {{ rtrim(rtrim($wallet->getCurrentBalance(), '0'), '.') }} {{ $wallet->unit }}</div>
                        @endif
                    </div>

                    <!-- Positions Preview (only for multi mode) -->
                    @if($wallet->mode === 'multi')
                        @php($positionsPreview = $wallet->positions()->limit(3)->get())
                        @if ($positionsPreview->isNotEmpty())
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-medium text-grey-inverse">{{ __('Recent positions') }}</div>
                                </div>
                                <div class="space-y-2">
                                    @foreach ($positionsPreview as $pos)
                                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-custom-accent/5 border border-custom-accent/10">
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium text-sm truncate">{{ $pos->name }}</div>
                                                @if ($pos->ticker)
                                                    <div class="text-xs text-grey-inverse">{{ $pos->ticker }}</div>
                                                @endif
                                            </div>
                                            <div class="text-sm font-medium ml-3">
                                                {{ rtrim(rtrim($pos->quantity, '0'), '.') }} {{ $pos->unit }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-6">
                                <div class="text-sm text-grey-inverse mb-3">{{ __('No positions yet') }}</div>
                                <flux:modal.trigger name="wallet-positions-{{ $wallet->id }}">
                                    <flux:button size="sm" variant="primary" icon="plus" as="button">{{ __('Add position') }}</flux:button>
                                </flux:modal.trigger>
                            </div>
                        @endif
                    @else
                        <!-- Single mode info -->
                        <div class="text-center py-6">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                                <flux:icon.check class="w-6 h-6 text-green-600" />
                            </div>
                            <div class="text-sm text-grey-inverse">{{ __('Simple wallet mode') }}</div>
                            <div class="text-xs text-grey-inverse mt-1">{{ __('Balance managed directly') }}</div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Render modals outside the grid so they don't become grid items -->
        @foreach ($wallets as $wallet)
            <!-- Edit Wallet Modal -->
            <flux:modal name="edit-wallet-{{ $wallet->id }}" class="!w-1/2 !max-w-none mx-auto">
                <livewire:money.wallet-form :wallet="$wallet" />
            </flux:modal>

            <!-- Wallet Positions Modal -->
            {{-- <flux:modal name="wallet-positions-{{ $wallet->id }}" class="!w-1/2 !max-w-none mx-auto">
                <livewire:money.wallet-positions :wallet="$wallet" />
            </flux:modal> --}}

            <!-- Delete Confirmation Modal -->
            <flux:modal name="delete-wallet-{{ $wallet->id }}" class="w-5/6">
                <div class="space-y-6">
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                            <flux:icon.trash class="w-6 h-6 text-red-600" />
                        </div>
                        <flux:heading size="xl" class="mb-2">{{ __('Delete wallet?') }}</flux:heading>
                        <flux:text class="text-grey-inverse">{{ __('This action cannot be undone. All positions in this wallet will be permanently deleted.') }}</flux:text>
                    </div>
                    <div class="flex justify-end gap-3">
                        <flux:button variant="ghost" @click="Flux.modals().close('delete-wallet-{{ $wallet->id }}')">{{ __('Cancel') }}</flux:button>
                        <flux:button variant="danger" wire:click="delete('{{ $wallet->id }}')">{{ __('Delete wallet') }}</flux:button>
                    </div>
                </div>
            </flux:modal>
        @endforeach
    @endif
</div>
