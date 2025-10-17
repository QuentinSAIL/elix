<div wire:click="edit" role="button" tabindex="0" aria-label="{{ __('Edit panel') }} {{ $title }}"
     class="cursor-pointer group">

    <!-- Panel Title -->
    <div class="mb-6">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-50 tracking-tight group-hover:text-zinc-700 dark:group-hover:text-zinc-300 transition-colors">
            {{ $title }}
        </h2>
    </div>

    <!-- Panel Content -->
    <div class="relative">
        @if($panel->type === 'number')
            <div class="text-center py-8">
                <div class="text-5xl font-bold bg-gradient-to-r from-primary-500 to-primary-600 dark:from-primarydark-500 dark:to-primarydark-400 bg-clip-text text-transparent mb-2 whitespace-nowrap">
                    {{ $this->formatAmount($values[0] ?? 0) }} €
                </div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Total Amount') }}</div>
            </div>
        @elseif($panel->type === 'gauge')
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-6 rounded-xl border border-green-200/50 dark:border-green-700/50">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-700 dark:text-green-400 mb-1 whitespace-nowrap">
                            {{ $this->formatAmount($values[0] ?? 0) }} €
                        </div>
                        <div class="text-sm font-medium text-green-600 dark:text-green-500">{{ __('Income') }}</div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-6 rounded-xl border border-red-200/50 dark:border-red-700/50">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-red-700 dark:text-red-400 mb-1 whitespace-nowrap">
                            {{ $this->formatAmount($values[1] ?? 0) }} €
                        </div>
                        <div class="text-sm font-medium text-red-600 dark:text-red-500">{{ __('Expenses') }}</div>
                    </div>
                </div>
            </div>
        @elseif($panel->type === 'trend')
            <div class="relative">
                <canvas wire:ignore id="{{ $panel->id }}" aria-label="{{ __('Chart displaying ') }} {{ $title }}"
                        class="w-full h-64"></canvas>
            </div>
        @elseif($panel->type === 'category_comparison')
            <div class="space-y-3">
                @foreach($labels as $index => $label)
                    <div class="flex items-center justify-between p-3 bg-zinc-50/50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200/50 dark:border-zinc-700/50 hover:bg-zinc-100/50 dark:hover:bg-zinc-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $colors[$index] ?? '#CCCCCC' }}"></div>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
                        </div>
                        <span class="font-bold {{ ($values[$index] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} whitespace-nowrap">
                            {{ $this->formatAmount($values[$index] ?? 0) }} €
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="relative">
                <canvas wire:ignore id="{{ $panel->id }}" aria-label="{{ __('Chart displaying ') }} {{ $title }}"
                        class="w-full h-64"></canvas>
                <div wire:ignore id="legend-{{ $panel->id }}" class="chart-legend mt-6 flex justify-center">
                    <div class="legend-item total-display flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-700 border border-zinc-200/50 dark:border-zinc-700/50 min-w-[200px] shadow-sm">
                        <span class="text-lg font-bold {{ (array_sum($values) ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} whitespace-nowrap">
                            {{ (array_sum($values) ?? 0) >= 0 ? '+' : '' }}{{ $this->formatAmount(array_sum($values)) }} €
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@if($panel->type !== 'number' && $panel->type !== 'gauge' && $panel->type !== 'category_comparison')
<script>
    document.addEventListener('livewire:navigated', function() {
        const panelId = @json($panel->id);
        const labels = @json($labels);
        const data = @json($values);
        const colors = @json($colors);
        const type = @json($panel->type);

        const ctx = document.getElementById(panelId).getContext('2d');

        // Simple chart configuration
        const chartConfig = {
            type: type === 'trend' ? 'line' : type,
            data: {
                labels: labels,
                datasets: [{
                    labels: labels,
                    data: data,
                    backgroundColor: type === 'trend' ? 'rgba(139, 92, 246, 0.2)' : [
                        ...colors,
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: type === 'trend' ? 'rgba(139, 92, 246, 1)' : undefined,
                    borderWidth: type === 'trend' ? 2 : 0,
                    fill: type === 'trend' ? true : false,
                    tension: type === 'trend' ? 0.4 : 0
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.9)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const value = context.parsed.y || context.parsed;
                                const formattedValue = Math.abs(value).toFixed(2) + ' €';
                                const sign = value >= 0 ? '+' : '-';
                                return sign + formattedValue;
                            },
                            afterLabel: function(context) {
                                const value = context.parsed.y || context.parsed;
                                const total = data.reduce((sum, val) => sum + Math.abs(val), 0);
                                const percentage = total > 0 ? ((Math.abs(value) / total) * 100).toFixed(1) : 0;
                                return percentage + '% {{ __('of total') }}';
                            }
                        }
                    }
                },
                scales: type === 'trend' ? {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(2) + ' €';
                            }
                        }
                    }
                } : undefined,
                onHover: function(event, elements) {
                    event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
                }
            }
        };

        new Chart(ctx, chartConfig);
    });
</script>
@endif
