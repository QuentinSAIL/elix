<div class="min-h-screen bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <div class="space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-50 tracking-tight mb-2">
                    @if($edition)
                        {{ __('Edit wallet') }}
                    @else
                        {{ __('Create wallet') }}
                    @endif
                </h1>
                <p class="text-zinc-600 dark:text-zinc-400">
                    @if($edition)
                        {{ __('Update your wallet details') }}
                    @else
                        {{ __('Set up a new wallet to track your assets') }}
                    @endif
                </p>
            </div>

            <!-- Wallet Form -->
            <div class="bg-white/70 dark:bg-zinc-800/70 backdrop-blur-sm rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm overflow-hidden">
                <form class="space-y-8">
                    <!-- Basic Information -->
                    <div class="px-8 py-6 border-b border-zinc-200/50 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50/50 dark:from-zinc-800/50 to-transparent">
                        <div class="text-center">
                            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 mb-2">{{ __('Basic Information') }}</h2>
                            <p class="text-zinc-600 dark:text-zinc-400">{{ __('Set up your wallet details') }}</p>
                        </div>
                    </div>

                    <div class="px-8 py-6 space-y-6">

                        <flux:field>
                            <flux:label>{{ __('Wallet name') }}</flux:label>
                            <flux:input
                                wire:model.lazy="walletForm.name"
                                placeholder="{{ __('e.g., My Investment Portfolio') }}"
                                autofocus
                                class="text-lg"
                            />
                            <flux:error name="walletForm.name" />
                            <flux:description>{{ __('Give your wallet a descriptive name') }}</flux:description>
                        </flux:field>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:field>
                                <flux:label>{{ __('Currency') }}</flux:label>
                                <flux:input wire:model.lazy="walletForm.unit" placeholder="{{ __('e.g., EUR, USD, BTC') }}" />
                                <flux:error name="walletForm.unit" />
                                <flux:description>{{ __('The currency unit for this wallet') }}</flux:description>
                            </flux:field>

                            @if($walletForm['mode'] === 'single')
                                <flux:field>
                                    <flux:label>{{ __('Initial balance') }}</flux:label>
                                    <flux:input
                                        type="number"
                                        step="any"
                                        wire:model.lazy="walletForm.balance"
                                        placeholder="0.00"
                                    />
                                    <flux:error name="walletForm.balance" />
                                    <flux:description>{{ __('Starting amount for this wallet') }}</flux:description>
                                </flux:field>
                            @else
                                <div class="flex items-center justify-center h-12 px-4 rounded-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200/50 dark:border-blue-700/50 self-end">
                                    <div class="flex items-center gap-2">
                                        <flux:icon.calculator class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                        <span class="text-blue-700 dark:text-blue-300 font-medium">{{ __('Balance calculated from positions') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Wallet Mode Selection -->
                    <div class="px-8 py-6 border-t border-zinc-200/50 dark:border-zinc-700/50">
                        <div class="text-center mb-6">
                            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 mb-2">{{ __('Wallet Type') }}</h2>
                            <p class="text-zinc-600 dark:text-zinc-400">{{ __('Choose the type that best fits your needs') }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Single Mode Card -->
                            <label class="relative cursor-pointer group">
                                <input
                                    type="radio"
                                    wire:model.lazy="walletForm.mode"
                                    value="single"
                                    class="sr-only"
                                />
                                <div class="rounded-xl border-2 p-6 transition-all duration-200 {{ $walletForm['mode'] === 'single' ? 'border-primary-500 dark:border-primarydark-500 bg-primary-50/50 dark:bg-primary-900/10' : 'border-zinc-300 dark:border-zinc-600 hover:border-zinc-400 dark:hover:border-zinc-500' }}">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="w-12 h-12 rounded-full {{ $walletForm['mode'] === 'single' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-zinc-100 dark:bg-zinc-700' }} flex items-center justify-center">
                                            <flux:icon.banknotes class="w-6 h-6 {{ $walletForm['mode'] === 'single' ? 'text-green-600 dark:text-green-400' : 'text-zinc-500 dark:text-zinc-400' }}" />
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Single Position') }}</h3>
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Simple wallet') }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Perfect for savings accounts, PEL, LDD, and simple balances') }}</p>
                                        <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                            <flux:icon.check class="w-3 h-3" />
                                            <span>{{ __('Direct balance management') }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                            <flux:icon.check class="w-3 h-3" />
                                            <span>{{ __('No complex tracking needed') }}</span>
                                        </div>
                                    </div>
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
                                <div class="rounded-xl border-2 p-6 transition-all duration-200 {{ $walletForm['mode'] === 'multi' ? 'border-primary-500 dark:border-primarydark-500 bg-primary-50/50 dark:bg-primary-900/10' : 'border-zinc-300 dark:border-zinc-600 hover:border-zinc-400 dark:hover:border-zinc-500' }}">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="w-12 h-12 rounded-full {{ $walletForm['mode'] === 'multi' ? 'bg-blue-100 dark:bg-blue-900/20' : 'bg-zinc-100 dark:bg-zinc-700' }} flex items-center justify-center">
                                            <flux:icon.chart-bar class="w-6 h-6 {{ $walletForm['mode'] === 'multi' ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500 dark:text-zinc-400' }}" />
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Multiple Positions') }}</h3>
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Investment portfolio') }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Perfect for investment portfolios with stocks, crypto, and multiple assets') }}</p>
                                        <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                            <flux:icon.check class="w-3 h-3" />
                                            <span>{{ __('Track multiple assets') }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                            <flux:icon.check class="w-3 h-3" />
                                            <span>{{ __('Automatic price updates') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-8 py-6 border-t border-zinc-200/50 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50/50 dark:from-zinc-800/50 to-transparent">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                wire:click="save"
                                wire:keydown.enter="save"
                                class="cursor-pointer inline-flex items-center justify-center px-6 py-3 rounded-lg bg-primary-500 dark:bg-primarydark-500 text-white text-sm font-medium hover:bg-primary-600 dark:hover:bg-primarydark-400 transition-colors duration-200 shadow-sm hover:shadow-md"
                            >
                                @if($edition)
                                    {{ __('Update wallet') }}
                                @else
                                    {{ __('Create wallet') }}
                                @endif
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if ($edition && $wallet && $wallet->isMultiMode())
                <!-- Positions Section -->
                <div class="bg-white/70 dark:bg-zinc-800/70 backdrop-blur-sm rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm overflow-hidden">
                    <div class="px-8 py-6 border-b border-zinc-200/50 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50/50 dark:from-zinc-800/50 to-transparent">
                        <div class="text-center">
                            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 mb-2">{{ __('Wallet positions') }}</h2>
                            <p class="text-zinc-600 dark:text-zinc-400">{{ __('Manage your investment positions') }}</p>
                        </div>
                    </div>

                    <!-- Existing Positions -->
                    @if ($positions->isNotEmpty())
                        <div class="px-8 py-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Current positions') }}</h3>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $positions->count() }} {{ $positions->count() === 1 ? __('position') : __('positions') }}</div>
                            </div>
                            <div class="space-y-3">
                                @foreach ($positions as $pos)
                                    <div class="group rounded-xl bg-zinc-50/50 dark:bg-zinc-800/50 border border-zinc-200/50 dark:border-zinc-700/50 p-4 hover:border-zinc-300/50 dark:hover:border-zinc-600/50 transition-all duration-200">
                                        <div class="flex items-center justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-50 truncate">{{ $pos->name }}</h4>
                                                    @if ($pos->ticker)
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 border border-primary-200/50 dark:border-primary-700/50">
                                                            {{ $pos->ticker }}
                                                        </span>
                                                    @endif
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400">
                                                        {{ $pos->unit }}
                                                    </span>
                                                </div>
                                                <div class="grid grid-cols-3 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Quantity') }}:</span>
                                                        <span class="font-medium ml-1 text-zinc-900 dark:text-zinc-50">{{ rtrim(rtrim($pos->quantity, '0'), '.') }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Price') }}:</span>
                                                        <span class="font-medium ml-1 text-zinc-900 dark:text-zinc-50">{{ rtrim(rtrim($pos->price, '0'), '.') }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Value') }}:</span>
                                                        <span class="font-medium ml-1 text-zinc-900 dark:text-zinc-50">
                                                            {{ $pos->getFormattedValue() }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity ml-4">
                                                <button
                                                    type="button"
                                                    wire:click="editPosition('{{ $pos->id }}')"
                                                    class="cursor-pointer p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                                    title="{{ __('Edit position') }}"
                                                >
                                                    <flux:icon.pencil class="w-4 h-4 text-zinc-500 dark:text-zinc-400" />
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="deletePosition('{{ $pos->id }}')"
                                                    class="cursor-pointer p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                                    title="{{ __('Delete position') }}"
                                                >
                                                    <flux:icon.trash class="w-4 h-4 text-red-600 dark:text-red-400" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Add/Edit Position Form -->
                    <div class="px-8 py-6 border-t border-zinc-200/50 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50/50 dark:from-zinc-800/50 to-transparent">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                                <flux:icon.plus class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">
                                @if($editingPosition)
                                    {{ __('Edit position') }}
                                @else
                                    {{ __('Add new position') }}
                                @endif
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:field>
                                <flux:label>{{ __('Asset name') }}</flux:label>
                                <flux:input wire:model.lazy="positionForm.name" placeholder="{{ __('e.g., Apple Inc.') }}" />
                                <flux:error name="positionForm.name" />
                                <flux:description>{{ __('The name of the asset or company') }}</flux:description>
                            </flux:field>
                            <flux:field>
                                <flux:label>{{ __('Ticker Symbol') }}</flux:label>
                                <flux:input wire:model.lazy="positionForm.ticker" placeholder="AAPL" />
                                <flux:error name="positionForm.ticker" />
                                <flux:description>{{ __('Stock symbol for automatic price updates') }}</flux:description>
                            </flux:field>
                            <flux:field>
                                <flux:label>{{ __('Unit Type') }}</flux:label>
                                <flux:select wire:model.lazy="positionForm.unit">
                                    <option value="">{{ __('Select unit type') }}</option>
                                    <option value="SHARE">{{ __('Share') }}</option>
                                    <option value="UNIT">{{ __('Unit') }}</option>
                                    <option value="TOKEN">{{ __('Token') }}</option>
                                    <option value="CRYPTO">{{ __('Crypto') }}</option>
                                    <option value="ETF">{{ __('ETF') }}</option>
                                    <option value="BOND">{{ __('Bond') }}</option>
                                    <option value="REAL_ASSET">{{ __('Real Asset (e.g. real estate, car, watch)') }}</option>
                                </flux:select>
                                <flux:error name="positionForm.unit" />
                                <flux:description>{{ __('Type of asset unit') }}</flux:description>
                            </flux:field>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>{{ __('Quantity') }}</flux:label>
                                <flux:input type="number" step="any" min="0" wire:model.lazy="positionForm.quantity" placeholder="10" />
                                <flux:error name="positionForm.quantity" />
                                <flux:description>{{ __('Number of units you own') }}</flux:description>
                            </flux:field>
                            <flux:field>
                                <flux:label>{{ __('Price per Unit') }}</flux:label>
                                <flux:input type="number" step="any" min="0" wire:model.lazy="positionForm.price" placeholder="150.00" />
                                <flux:error name="positionForm.price" />
                                <flux:description>{{ __('Current price per unit (auto-updated if ticker provided)') }}</flux:description>
                            </flux:field>
                        </div>

                        <div class="flex justify-end gap-3">
                            @if($editingPosition)
                                <button
                                    type="button"
                                    wire:click="cancelEditPosition"
                                    class="cursor-pointer px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                >
                                    {{ __('Cancel') }}
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="savePosition"
                                class="cursor-pointer px-4 py-2 rounded-lg bg-primary-500 dark:bg-primarydark-500 text-white hover:bg-primary-600 dark:hover:bg-primarydark-400 transition-colors"
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
</div>
