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
            <flux:button wire:click="$refresh" variant="outline" icon="arrow-path">
                {{ __('Refresh') }}
            </flux:button>
        </div>
    </div>

    <!-- Add Position Form -->
    <div class="mb-8">
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">
                    {{ $editing ? __('Edit Position') : __('Add New Position') }}
                </flux:heading>
            </flux:card.header>
            
            <flux:card.body>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Name') }}</flux:label>
                        <flux:input wire:model="positionForm.name" placeholder="{{ __('e.g., Apple Inc.') }}" />
                        <flux:error name="positionForm.name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Ticker Symbol') }}</flux:label>
                        <flux:input wire:model="positionForm.ticker" placeholder="{{ __('e.g., AAPL') }}" />
                        <flux:error name="positionForm.ticker" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Unit Type') }}</flux:label>
                        <flux:select wire:model="positionForm.unit">
                            <flux:option value="SHARE">{{ __('Share') }}</flux:option>
                            <flux:option value="UNIT">{{ __('Unit') }}</flux:option>
                            <flux:option value="TOKEN">{{ __('Token') }}</flux:option>
                        </flux:select>
                        <flux:error name="positionForm.unit" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Quantity') }}</flux:label>
                        <flux:input type="number" wire:model="positionForm.quantity" step="0.000001" min="0" />
                        <flux:error name="positionForm.quantity" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Price per Unit') }} ({{ $this->getCurrencySymbol() }})</flux:label>
                        <flux:input type="number" wire:model="positionForm.price" step="0.01" min="0" />
                        <flux:error name="positionForm.price" />
                    </flux:field>
                </div>

                <div class="flex gap-2 mt-4">
                    <flux:button wire:click="save" variant="primary">
                        {{ $editing ? __('Update Position') : __('Add Position') }}
                    </flux:button>
                    
                    @if($editing)
                        <flux:button wire:click="resetForm" variant="outline">
                            {{ __('Cancel') }}
                        </flux:button>
                    @endif
                </div>
            </flux:card.body>
        </flux:card>
    </div>

    <!-- Positions List -->
    <div class="space-y-4">
        @forelse($positions as $position)
            <flux:card>
                <flux:card.body>
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
                            <flux:button wire:click="edit('{{ $position->id }}')" variant="outline" size="sm">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button wire:click="delete('{{ $position->id }}')" variant="outline" size="sm" color="red">
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </div>
                </flux:card.body>
            </flux:card>
        @empty
            <flux:card>
                <flux:card.body>
                    <div class="text-center py-8">
                        <flux:icon name="chart-bar" class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('No positions') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Get started by adding your first position.') }}
                        </p>
                    </div>
                </flux:card.body>
            </flux:card>
        @endforelse
    </div>
</div>
