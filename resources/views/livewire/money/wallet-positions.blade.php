<div class="min-h-screen bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800">
    <div class="max-w-7xl mx-auto px-6 py-12">
        <!-- Header Section -->
        <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border-b border-zinc-200/50 dark:border-zinc-700/50 sticky top-0 z-10 -mx-6 px-6 py-8 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-50 tracking-tight">{{ __('Portfolio Positions') }}</h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mt-2">{{ __('Manage your investment positions and track their performance') }}</p>
                    <div class="mt-3">
                        <div class="inline-flex items-center px-4 py-2 rounded-xl bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 border border-primary-200/50 dark:border-primary-700/50">
                            <flux:icon.banknotes class="w-5 h-5 text-primary-600 dark:text-primary-400 mr-2" />
                            <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">
                                {{ __('Total Value') }}: {{ $this->getCurrencySymbol() }}{{ number_format($this->getTotalValue(), 2) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="updatePrices" class="cursor-pointer inline-flex items-center px-4 py-2 rounded-lg bg-primary-500 dark:bg-primarydark-500 text-white hover:bg-primary-600 dark:hover:bg-primarydark-400 transition-colors duration-200">
                        <flux:icon.arrow-path class="w-4 h-4 mr-2" />
                        {{ __('Update Prices') }}
                    </button>
                    <button wire:click="$refresh" class="cursor-pointer inline-flex items-center px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors duration-200">
                        <flux:icon.arrow-path class="w-4 h-4 mr-2" />
                        {{ __('Refresh') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Position Form -->
        <div class="mb-8">
            <div class="bg-white/70 dark:bg-zinc-800/70 backdrop-blur-sm rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-zinc-200/50 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50/50 dark:from-zinc-800/50 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                            <flux:icon.plus class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">
                            {{ $editing ? __('Edit Position') : __('Add New Position') }}
                        </h2>
                    </div>
                </div>

                <div class="px-8 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                        <div>
                            <flux:field>
                                <flux:label>{{ __('Name') }}</flux:label>
                                <flux:input wire:model="positionForm.name" placeholder="{{ __('e.g., Apple Inc.') }}" />
                                <flux:error name="positionForm.name" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>{{ __('Ticker Symbol') }}</flux:label>
                                <flux:input wire:model="positionForm.ticker" placeholder="{{ __('e.g., AAPL') }}" />
                                <flux:error name="positionForm.ticker" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>{{ __('Unit Type') }}</flux:label>
                                <flux:select wire:model="positionForm.unit">
                                    <option value="SHARE">{{ __('Share') }}</option>
                                    <option value="UNIT">{{ __('Unit') }}</option>
                                    <option value="TOKEN">{{ __('Token') }}</option>
                                    <option value="CRYPTO">{{ __('Crypto') }}</option>
                                </flux:select>
                                <flux:error name="positionForm.unit" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>{{ __('Quantity') }}</flux:label>
                                <flux:input type="number" wire:model="positionForm.quantity" step="0.000001" min="0" />
                                <flux:error name="positionForm.quantity" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>{{ __('Price per Unit') }} ({{ $this->getCurrencySymbol() }})</flux:label>
                                <flux:input type="number" wire:model="positionForm.price" step="0.01" min="0" />
                                <flux:error name="positionForm.price" />
                            </flux:field>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <flux:button wire:click="save" variant="primary">
                            {{ $editing ? __('Update Position') : __('Add Position') }}
                        </flux:button>

                        @if($editing)
                            <flux:button wire:click="resetForm" variant="ghost">
                                {{ __('Cancel') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Price Synchronization Warning -->
        @php
            $tickerPrices = [];
            $inconsistentTickers = [];

            foreach($positions as $position) {
                if($position->ticker) {
                    $ticker = strtoupper($position->ticker);
                    if(!isset($tickerPrices[$ticker])) {
                        $tickerPrices[$ticker] = [];
                    }
                    $tickerPrices[$ticker][] = (float)$position->price;
                }
            }

            foreach($tickerPrices as $ticker => $prices) {
                if(count(array_unique($prices)) > 1) {
                    $inconsistentTickers[] = $ticker;
                }
            }
        @endphp

        @if(count($inconsistentTickers) > 0)
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 rounded-xl p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <flux:icon.exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('Price Inconsistency Detected') }}
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>{{ __('The following tickers have different prices across positions:') }}</p>
                            <ul class="mt-1 list-disc list-inside">
                                @foreach($inconsistentTickers as $ticker)
                                    <li><strong>{{ $ticker }}</strong></li>
                                @endforeach
                            </ul>
                            <p class="mt-2">{{ __('Click "Update Prices" to synchronize all positions with the same ticker.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Positions List -->
        <div class="space-y-6">
            @forelse($positions as $position)
                <div class="group bg-white/70 dark:bg-zinc-800/70 backdrop-blur-sm rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm hover:shadow-lg dark:hover:shadow-zinc-900/50 transition-all duration-300 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">
                                            {{ $position->name }}
                                        </h3>
                                        @if($position->ticker)
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 border border-primary-200/50 dark:border-primary-700/50">
                                                    {{ $position->ticker }}
                                                </span>
                                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $position->unit }}</span>
                                            </div>
                                        @else
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                                {{ $position->unit }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="text-right">
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ number_format($position->quantity, 6) }} {{ $position->unit }}
                                        </div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            @if($position->ticker)
                                                @php
                                                    $currentPrice = $this->getCurrentPrice($position);
                                                    $currentValue = $this->getCurrentValue($position);
                                                @endphp

                                                @if($currentPrice !== null)
                                                    {{ $this->getCurrencySymbol() }}{{ number_format($currentPrice, 2) }} / {{ $position->unit }}
                                                @else
                                                    {{ $this->getCurrencySymbol() }}{{ number_format($position->price, 2) }} / {{ $position->unit }}
                                                @endif
                                            @else
                                                {{ $this->getCurrencySymbol() }}{{ number_format($position->price, 2) }} / {{ $position->unit }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right ml-6">
                                <div class="text-2xl font-bold bg-gradient-to-r from-primary-500 to-primary-600 dark:from-primarydark-500 dark:to-primarydark-400 bg-clip-text text-transparent mb-1 whitespace-nowrap">
                                    @if($position->ticker)
                                        @php
                                            $currentValue = $this->getCurrentValue($position);
                                        @endphp

                                        @if($currentValue !== null)
                                            {{ $this->getCurrencySymbol() }}{{ number_format($currentValue, 2) }}
                                        @else
                                            {{ $this->getCurrencySymbol() }}{{ number_format((float)$position->quantity * (float)$position->price, 2) }}
                                        @endif
                                    @else
                                        {{ $this->getCurrencySymbol() }}{{ number_format((float)$position->quantity * (float)$position->price, 2) }}
                                    @endif
                                </div>

                                @if($position->ticker)
                                    @php
                                        $currentPrice = $this->getCurrentPrice($position);
                                        $originalValue = (float)$position->quantity * (float)$position->price;
                                        $currentValue = $this->getCurrentValue($position);
                                    @endphp

                                    @if($currentValue !== null && $currentPrice !== null)
                                        @php
                                            $change = $currentValue - $originalValue;
                                            $changePercent = $originalValue > 0 ? ($change / $originalValue) * 100 : 0;
                                        @endphp

                                        <div class="text-sm font-medium {{ $change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $change >= 0 ? '+' : '' }}{{ $this->getCurrencySymbol() }}{{ number_format($change, 2) }}
                                            ({{ $change >= 0 ? '+' : '' }}{{ number_format($changePercent, 2) }}%)
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <div class="flex gap-2 ml-6 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                @if($position->ticker)
                                    <button wire:click="updatePositionPrice('{{ $position->id }}')" class="cursor-pointer p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors" title="{{ __('Update price') }}">
                                        <flux:icon.arrow-path class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    </button>
                                @endif
                                <button wire:click="edit('{{ $position->id }}')" class="cursor-pointer p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="{{ __('Edit position') }}">
                                    <flux:icon.pencil class="w-4 h-4 text-zinc-500 dark:text-zinc-400" />
                                </button>
                                <button wire:click="delete('{{ $position->id }}')" class="cursor-pointer p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="{{ __('Delete position') }}">
                                    <flux:icon.trash class="w-4 h-4 text-red-600 dark:text-red-400" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700 rounded-2xl flex items-center justify-center mb-6">
                        <flux:icon.chart-bar class="w-12 h-12 text-zinc-400 dark:text-zinc-500" />
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50 mb-2">{{ __('No positions') }}</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-8 max-w-md">{{ __('Get started by adding your first position to track your investments') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
