<div>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ __('Portfolio Positions') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Total Value') }}: {{ $this->getCurrencySymbol() }}{{ number_format($this->getTotalValue(), 2) }}
                </p>
            </div>
            <button wire:click="$refresh" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                {{ __('Refresh') }}
            </button>
        </div>
    </div>

    <!-- Add Position Form -->
    <div class="mb-8">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $editing ? __('Edit Position') : __('Add New Position') }}
                </h3>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                        <input wire:model="positionForm.name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="{{ __('e.g., Apple Inc.') }}" />
                        @error('positionForm.name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Ticker Symbol') }}</label>
                        <input wire:model="positionForm.ticker" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="{{ __('e.g., AAPL') }}" />
                        @error('positionForm.ticker')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Unit Type') }}</label>
                        <select wire:model="positionForm.unit" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="SHARE">{{ __('Share') }}</option>
                            <option value="UNIT">{{ __('Unit') }}</option>
                            <option value="TOKEN">{{ __('Token') }}</option>
                        </select>
                        @error('positionForm.unit')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Quantity') }}</label>
                        <input type="number" wire:model="positionForm.quantity" step="0.000001" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        @error('positionForm.quantity')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Price per Unit') }} ({{ $this->getCurrencySymbol() }})</label>
                        <input type="number" wire:model="positionForm.price" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        @error('positionForm.price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button wire:click="save" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        {{ $editing ? __('Update Position') : __('Add Position') }}
                    </button>

                    @if($editing)
                        <button wire:click="resetForm" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                            {{ __('Cancel') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Positions List -->
    <div class="space-y-4">
        @forelse($positions as $position)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $position->name }}
                                    </h3>
                                    @if($position->ticker)
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $position->ticker }} • {{ $position->unit }}
                                        </p>
                                    @else
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $position->unit }}
                                        </p>
                                    @endif
                                </div>

                                <div class="text-right">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ number_format($position->quantity, 6) }} {{ $position->unit }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
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

                        <div class="text-right ml-4">
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
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

                                    <div class="text-sm {{ $change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $change >= 0 ? '+' : '' }}{{ $this->getCurrencySymbol() }}{{ number_format($change, 2) }}
                                        ({{ $change >= 0 ? '+' : '' }}{{ number_format($changePercent, 2) }}%)
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div class="flex gap-2 ml-4">
                            <button wire:click="edit('{{ $position->id }}')" class="inline-flex items-center rounded-md border border-gray-300 px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                                {{ __('Edit') }}
                            </button>
                            <button wire:click="delete('{{ $position->id }}')" class="inline-flex items-center rounded-md border border-red-300 px-2 py-1 text-sm font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:border-red-600 dark:hover:bg-red-900/20">
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
                <div class="p-4">
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6m6 13V6m-3 13V6" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('No positions') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Get started by adding your first position.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
