<div>
    <div class="bg-custom rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start mb-6">
            <h2 class="text-2xl font-bold mb-4">Statistiques financières</h2>

            <div class="flex flex-col md:flex-row gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type de graphique</label>
                    <select wire:model.live="chartType"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full">
                        <option value="bar">Barres</option>
                        <option value="line">Ligne</option>
                        <option value="pie">Camembert (catégories uniquement)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grouper par</label>
                    <select wire:model="groupBy"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full">
                        <option value="day">Jour</option>
                        <option value="week">Semaine</option>
                        <option value="month">Mois</option>
                        <option value="category">Catégorie</option>
                        <option value="account">Compte</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                    <select wire:model="dateRange"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full">
                        <option value="today">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="quarter">Ce trimestre</option>
                        <option value="year">Cette année</option>
                        <option value="custom">Personnalisée</option>
                    </select>
                </div>
            </div>
        </div>

        @if ($dateRange === 'custom')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" wire:model="customStartDate"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" wire:model="customEndDate"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full">
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comptes bancaires</label>
                <select wire:model="selectedAccounts" multiple
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full"
                    size="3">
                    @foreach ($availableAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->currency }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catégories</label>
                <select wire:model="selectedCategories" multiple
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full"
                    size="3">
                    @foreach ($availableCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <p class="text-sm text-gray-500 mt-1">Laissez vide pour toutes les catégories</p>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-6">
            <label class="inline-flex items-center">
                <input type="checkbox" wire:model="showIncome"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <span class="ml-2">Afficher les revenus</span>
            </label>

            <label class="inline-flex items-center">
                <input type="checkbox" wire:model="showExpense"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <span class="ml-2">Afficher les dépenses</span>
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="p-4 bg-green-50 rounded-lg">
                <h3 class="text-lg font-medium text-green-700">Revenus</h3>
                <p class="text-2xl font-bold text-green-600">{{ number_format($totalIncome, 2) }} €</p>
            </div>

            <div class="p-4 bg-red-50 rounded-lg">
                <h3 class="text-lg font-medium text-red-700">Dépenses</h3>
                <p class="text-2xl font-bold text-red-600">{{ number_format($totalExpense, 2) }} €</p>
            </div>

            <div class="p-4 {{ $netAmount >= 0 ? 'bg-blue-50' : 'bg-orange-50' }} rounded-lg">
                <h3 class="text-lg font-medium {{ $netAmount >= 0 ? 'text-blue-700' : 'text-orange-700' }}">Solde net
                </h3>
                <p class="text-2xl font-bold {{ $netAmount >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                    {{ number_format($netAmount, 2) }} €</p>
            </div>
        </div>

        <!-- Graphique -->
        <div class="w-full" style="height: 400px;" x-data="{
            chart: null,
            initChart(chartData, categoryColors) {
                const canvas = this.$refs.chartCanvas;
                if (!canvas) return;

                if (this.chart) {
                    this.chart.destroy();
                }

                if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                    this.$refs.chartContainer.innerHTML = '<div class="flex items-center justify-center h-full
            bg-gray-50 rounded-lg">
            <p class="text-gray-500">Aucune donnée disponible pour la période sélectionnée</p>
        </div>';
        return;
        }

        const ctx = canvas.getContext('2d');
        const chartType = @this.chartType;
        const groupBy = @this.groupBy;
        const effectiveType = (chartType === 'pie' && groupBy !== 'category') ? 'bar' : chartType;

        const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
        legend: { position: 'top' },
        tooltip: {
        callbacks: {
        label: ctx => `${ctx.dataset.label}: ${ctx.raw.toFixed(2)} €`
        }
        }
        }
        };

        if (effectiveType === 'pie') {
        this.chart = new Chart(ctx, {
        type: 'pie',
        data: {
        labels: chartData.labels,
        datasets: [{
        data: chartData.datasets.find(d => d.label === 'Dépenses').data,
        backgroundColor: categoryColors
        }]
        },
        options: {
        ...options,
        plugins: {
        ...options.plugins,
        tooltip: {
        callbacks: {
        label: ctx => `${ctx.label}: ${ctx.raw.toFixed(2)} €`
        }
        }
        }
        }
        });
        } else {
        this.chart = new Chart(ctx, {
        type: effectiveType,
        data: chartData,
        options
        });
        }
        }
        }"
        x-init="initChart(@json($chartData), @json(collect($categoryBreakdown)->pluck('color')->toArray()))"
        @chart-data-updated.window="initChart($event.detail.chartData, $event.detail.categoryColors)"
        >
        <div x-ref="chartContainer" class="w-full h-full">
            <canvas x-ref="chartCanvas" class="w-full h-full"></canvas>
        </div>
    </div>
</div>

@if ($groupBy !== 'category' && count($categoryBreakdown) > 0)
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold mb-4">Répartition par catégorie</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th
                            class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Catégorie</th>
                        <th
                            class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Revenus</th>
                        <th
                            class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dépenses</th>
                        <th
                            class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Net</th>
                        <th
                            class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Transactions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($categoryBreakdown as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-4 w-4 rounded-full"
                                        style="background-color: {{ $item['color'] }}"></div>
                                    <div class="ml-4">{{ $item['label'] }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 font-medium">
                                {{ number_format($item['income'], 2) }} €
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600 font-medium">
                                {{ number_format($item['expense'], 2) }} €
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium {{ $item['net'] >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                                {{ number_format($item['net'], 2) }} €
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                {{ $item['count'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('livewire:init', () => {
        let chart = null;

        function initChart(chartData, categoryColors) {
            // Récupération du canvas et de son contexte
            const canvas = document.getElementById('statistics-chart');
            if (!canvas) return;

            // Détruit l'ancien graphique s'il existe
            if (chart) {
                chart.destroy();
            }

            // Vérifie si nous avons des données à afficher
            if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                canvas.innerHTML =
                    '<div class="flex items-center justify-center h-full bg-gray-50 rounded-lg"><p class="text-gray-500">Aucune donnée disponible pour la période sélectionnée</p></div>';
                return;
            }

            // Crée un nouveau canvas pour le graphique
            const newCanvas = document.createElement('canvas');
            canvas.innerHTML = '';
            canvas.appendChild(newCanvas);
            const ctx = newCanvas.getContext('2d');

            // Choix du type de graphique effectif
            const chartType = @this.chartType;
            const groupBy = @this.groupBy;
            const effectiveType = (chartType === 'pie' && groupBy !== 'category') ? 'bar' : chartType;

            // Options communes
            const options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${ctx.raw.toFixed(2)} €`
                        }
                    }
                }
            };

            // Cas du camembert
            if (effectiveType === 'pie') {
                chart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.datasets.find(d => d.label === 'Dépenses').data,
                            backgroundColor: categoryColors
                        }]
                    },
                    options: {
                        ...options,
                        plugins: {
                            ...options.plugins,
                            tooltip: {
                                callbacks: {
                                    label: ctx => `${ctx.label}: ${ctx.raw.toFixed(2)} €`
                                }
                            }
                        }
                    }
                });
            } else {
                chart = new Chart(ctx, {
                    type: effectiveType,
                    data: chartData,
                    options
                });
            }
        }

        // Initialisation au chargement, avec injection des données PHP
        initChart(
            @json($chartData),
            @json(collect($categoryBreakdown)->pluck('color')->toArray())
        );

        // Écoute des mises à jour émises depuis le composant Livewire
        Livewire.on('chartDataUpdated', (event) => {
            initChart(event.detail.chartData, event.detail.categoryColors);
        });
    });
</script>
