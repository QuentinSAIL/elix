<div class="">
    <div class="space-y-8 w-full">
        <!-- Header -->
        <div class="text-center">
            <flux:heading size="2xl" class="mb-2">
                @if($edition)
                    {{ __('Edit wallet') }}
                @else
                    {{ __('Create wallet') }}
                @endif
            </flux:heading>
            <flux:text class="text-grey-inverse">
                @if($edition)
                    {{ __('Update your wallet details') }}
                @else
                    {{ __('Set up a new wallet to track your assets') }}
                @endif
            </flux:text>
        </div>

        <!-- Wallet Form -->
        <div class="">
            <div class="rounded-2xl bg-custom-ultra border border-custom-accent p-8">
                <form class="space-y-8">
                    <!-- Basic Information -->
                    <div class="space-y-6">
                        <div class="text-center">
                            <flux:heading size="lg" class="mb-2">{{ __('Basic Information') }}</flux:heading>
                            <flux:text class="text-grey-inverse">{{ __('Set up your wallet details') }}</flux:text>
                        </div>

                        <flux:input
                            :label="__('Wallet name')"
                            wire:model.lazy="walletForm.name"
                            placeholder="{{ __('e.g., My Investment Portfolio') }}"
                            autofocus
                            class="text-lg"
                        />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input
                                :label="__('Currency')"
                                wire:model.lazy="walletForm.unit"
                                placeholder="EUR, USD, BTC..."
                            />

                            @if($walletForm['mode'] === 'single')
                                <flux:input
                                    :label="__('Initial balance')"
                                    type="number"
                                    step="any"
                                    wire:model.lazy="walletForm.balance"
                                    placeholder="0.00"
                                />
                            @else
                                <div class="flex items-center justify-center h-12 px-4 rounded-lg bg-custom-accent/5 border border-custom-accent/20 self-end">
                                    <flux:text class="text-grey-inverse">{{ __('Balance calculated from positions') }}</flux:text>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Wallet Mode Selection -->
                    <div class="space-y-6">
                        <div class="text-center">
                            <flux:heading size="lg" class="mb-2">{{ __('Wallet Type') }}</flux:heading>
                            <flux:text class="text-grey-inverse">{{ __('Choose the type that best fits your needs') }}</flux:text>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Single Mode Card -->
                            <label class="relative cursor-pointer group">
                                <input
                                    type="radio"
                                    wire:model.lazy="walletForm.mode"
                                    value="single"
                                    class="sr-only"
                                />
                                <div class="rounded-xl border-2 p-6 transition-all duration-200 {{ $walletForm['mode'] === 'single' ? 'border-custom-accent bg-custom-accent/5' : 'border-custom-accent/20 hover:border-custom-accent/40' }}">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="w-12 h-12 rounded-full {{ $walletForm['mode'] === 'single' ? 'bg-green-100' : 'bg-grey-inverse/10' }} flex items-center justify-center">
                                            <flux:icon.banknotes class="w-6 h-6 {{ $walletForm['mode'] === 'single' ? 'text-green-600' : 'text-grey-inverse' }}" />
                                        </div>
                                        <div>
                                            <flux:heading size="md">{{ __('Single Position') }}</flux:heading>
                                            <flux:text class="text-sm text-grey-inverse">{{ __('Simple wallet') }}</flux:text>
                                        </div>
                                    </div>
                                    <flux:text class="text-sm text-grey-inverse">{{ __('Perfect for savings accounts, PEL, LDD, and simple balances') }}</flux:text>
                                </div>
                            </label>

                            <!-- Multi Mode Card -->
                            <label class="relative cursor-pointer group">
                                <input
                                    type="radio"
                                    wire:model.lazy="walletForm.mode"
                                    value="multi"
                                    class="sr-only"
                                />
                                <div class="rounded-xl border-2 p-6 transition-all duration-200 {{ $walletForm['mode'] === 'multi' ? 'border-custom-accent bg-custom-accent/5' : 'border-custom-accent/20 hover:border-custom-accent/40' }}">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="w-12 h-12 rounded-full {{ $walletForm['mode'] === 'multi' ? 'bg-blue-100' : 'bg-grey-inverse/10' }} flex items-center justify-center">
                                            <flux:icon.chart-bar class="w-6 h-6 {{ $walletForm['mode'] === 'multi' ? 'text-blue-600' : 'text-grey-inverse' }}" />
                                        </div>
                                        <div>
                                            <flux:heading size="md">{{ __('Multiple Positions') }}</flux:heading>
                                            <flux:text class="text-sm text-grey-inverse">{{ __('Investment portfolio') }}</flux:text>
                                        </div>
                                    </div>
                                    <flux:text class="text-sm text-grey-inverse">{{ __('Perfect for investment portfolios with stocks, crypto, and multiple assets') }}</flux:text>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end pt-6 border-t border-custom-accent/20">
                        <button
                            type="button"
                            wire:click="save"
                            wire:keydown.enter="save"
                            class="cursor-pointer inline-flex items-center justify-center px-4 py-2 rounded-lg bg-custom-accent text-white text-sm font-medium hover:bg-custom-accent/90 transition"
                        >
                            @if($edition)
                                {{ __('Update wallet') }}
                            @else
                                {{ __('Create wallet') }}
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($edition && $wallet && $wallet->isMultiMode())
            <!-- Positions Section -->
            <div class="border-t border-custom-accent/20 pt-8">
                <div class="text-center mb-6">
                    <flux:heading size="lg" class="mb-2">{{ __('Wallet positions') }}</flux:heading>
                    <flux:text class="text-grey-inverse">{{ __('Manage your investment positions') }}</flux:text>
                </div>

                <!-- Existing Positions -->
                @if ($positions->isNotEmpty())
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <flux:heading size="md">{{ __('Current positions') }}</flux:heading>
                            <div class="text-sm text-grey-inverse">{{ $positions->count() }} {{ $positions->count() === 1 ? __('position') : __('positions') }}</div>
                        </div>
                        <div class="space-y-3">
                            @foreach ($positions as $pos)
                                <div class="group rounded-xl bg-custom-ultra border border-custom-accent p-4 hover:border-custom-accent/50 transition-all duration-200">
                                    <div class="flex items-center justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <flux:heading size="sm" class="truncate">{{ $pos->name }}</flux:heading>
                                                @if ($pos->ticker)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-custom-accent/10 text-custom-accent border border-custom-accent/20">
                                                        {{ $pos->ticker }}
                                                    </span>
                                                @endif
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-grey-inverse/10 text-grey-inverse">
                                                    {{ $pos->unit }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-3 gap-4 text-sm">
                                                <div>
                                                    <span class="text-grey-inverse">{{ __('Quantity') }}:</span>
                                                    <span class="font-medium ml-1">{{ rtrim(rtrim($pos->quantity, '0'), '.') }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-grey-inverse">{{ __('Price') }}:</span>
                                                    <span class="font-medium ml-1">{{ rtrim(rtrim($pos->price, '0'), '.') }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-grey-inverse">{{ __('Value') }}:</span>
                                                    <span class="font-medium ml-1">
                                                        {{ $pos->getFormattedValue() ?? '—' }}
                                                    </span>
                                               </div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity ml-4">
                                            <button
                                                type="button"
                                                wire:click="editPosition('{{ $pos->id }}')"
                                                class="cursor-pointer p-2 rounded-lg hover:bg-custom-accent/10 transition-colors"
                                                title="{{ __('Edit position') }}"
                                            >
                                                <flux:icon.pencil class="w-4 h-4 text-grey-inverse" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="deletePosition('{{ $pos->id }}')"
                                                class="cursor-pointer p-2 rounded-lg hover:bg-red-100 transition-colors"
                                                title="{{ __('Delete position') }}"
                                            >
                                                <flux:icon.trash class="w-4 h-4 text-red-600" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Add/Edit Position Form -->
                <div class="rounded-xl bg-custom-accent/5 border border-custom-accent/20 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-full bg-custom-accent/10 flex items-center justify-center">
                            <flux:icon.plus class="w-4 h-4 text-custom-accent" />
                        </div>
                        <flux:heading size="md">
                            @if($editingPosition)
                                {{ __('Edit position') }}
                            @else
                                {{ __('Add new position') }}
                            @endif
                        </flux:heading>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:input
                            :label="__('Asset name')"
                            wire:model.lazy="positionForm.name"
                            placeholder="{{ __('e.g., Apple Inc.') }}"
                        />
                        <flux:input
                            :label="__('Ticker (optional)')"
                            wire:model.lazy="positionForm.ticker"
                            placeholder="AAPL"
                        />
                        <flux:input
                            :label="__('Unit')"
                            wire:model.lazy="positionForm.unit"
                            placeholder="SHARE, ETF, TOKEN..."
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <flux:input
                            :label="__('Quantity')"
                            type="number"
                            step="any"
                            wire:model.lazy="positionForm.quantity"
                            placeholder="10"
                        />
                        <flux:input
                            :label="__('Price (will be updated automatically if found on the internet)')"
                            type="number"
                            step="any"
                            wire:model.lazy="positionForm.price"
                            placeholder="150.00"
                        />
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        @if($editingPosition)
                            <button
                                type="button"
                                wire:click="cancelEditPosition"
                                class="cursor-pointer px-4 py-2 rounded-lg border border-custom-accent/20 text-grey-inverse hover:bg-custom-accent/5 transition-colors"
                            >
                                {{ __('Cancel') }}
                            </button>
                        @endif
                        <button
                            type="button"
                            wire:click="savePosition"
                            class="cursor-pointer px-4 py-2 rounded-lg bg-custom-accent text-white hover:bg-custom-accent/90 transition-colors"
                        >
                            @if($editingPosition)
                                {{ __('Update position') }}
                            @else
                                {{ __('Add position') }}
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
