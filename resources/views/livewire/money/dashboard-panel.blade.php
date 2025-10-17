<div wire:click="edit" role="button" tabindex="0" aria-label="{{ __('Edit panel') }} {{ $title }}">
    <h2 class="text-2xl font-bold mb-8">{{ $title }}</h2>

    @if($panel->type === 'number')
        <div class="text-center">
            <div class="text-6xl font-bold text-blue-600 mb-2">
                {{ number_format($values[0] ?? 0, 2) }} €
            </div>
            <div class="text-sm text-gray-500">{{ __('Total Amount') }}</div>
        </div>
    @elseif($panel->type === 'gauge')
        <div class="grid grid-cols-2 gap-4 text-center">
            <div class="bg-green-100 p-4 rounded-lg">
                <div class="text-3xl font-bold text-green-600">
                    {{ number_format($values[0] ?? 0, 2) }} €
                </div>
                <div class="text-sm text-green-700">{{ __('Income') }}</div>
            </div>
            <div class="bg-red-100 p-4 rounded-lg">
                <div class="text-3xl font-bold text-red-600">
                    {{ number_format($values[1] ?? 0, 2) }} €
                </div>
                <div class="text-sm text-red-700">{{ __('Expenses') }}</div>
            </div>
        </div>
    @elseif($panel->type === 'trend')
        <canvas wire:ignore id="{{ $panel->id }}" aria-label="{{ __('Chart displaying ') }} {{ $title }}"></canvas>
    @elseif($panel->type === 'category_comparison')
        <div class="space-y-2">
            @foreach($labels as $index => $label)
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span class="font-medium">{{ $label }}</span>
                    <span class="font-bold {{ ($values[$index] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($values[$index] ?? 0, 2) }} €
                    </span>
                </div>
            @endforeach
        </div>
    @else
        <canvas wire:ignore id="{{ $panel->id }}" aria-label="{{ __('Chart displaying ') }} {{ $title }}"></canvas>
        <div wire:ignore id="legend-{{ $panel->id }}" class="chart-legend mt-4 flex justify-center">
            <div class="legend-item total-display flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-white border border-gray-200 min-w-[200px]">
                <span class="text-lg font-bold {{ ($values[0] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Total: {{ ($values[0] ?? 0) >= 0 ? '+' : '' }}{{ number_format(array_sum($values), 2) }} €
                </span>
            </div>
        </div>
    @endif
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
                                return percentage + '% du total';
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
