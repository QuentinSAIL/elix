<div class="p-6 rounded-lg">
    <!-- Header avec navigation et informations -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-8">
        <!-- Titre (1/4) -->
        <div class="lg:col-span-1">
            <h3 class="text-2xl font-semibold text-custom">
                {{ __('Budget Overview') }}
            </h3>
            <p class="text-sm text-grey-inverse mt-1">
                {{ __('Track your spending against your monthly budgets') }}
            </p>
        </div>

        <!-- Résumé du budget total (2/4) -->
        <div class="lg:col-span-2 xl:mx-16">
            @if ($totalBudget > 0)
                <div class="p-4 rounded-lg {{ $isOverspent ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if ($isOverspent)
                                <flux:icon.exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                                <span class="font-medium text-red-800 dark:text-red-300">{{ __('Budget exceeded') }}</span>
                            @else
                                <flux:icon.check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                <span class="font-medium text-emerald-800 dark:text-emerald-300">{{ __('Budget respected') }}</span>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-grey-inverse">
                                {{ __('Budget') }}: {{ number_format($totalBudget, 2, ',', ' ') }} €
                            </div>
                            <div class="text-sm text-grey-inverse">
                                {{ __('Spent') }}: {{ number_format(-$totalSpent, 2, ',', ' ') }} €
                            </div>
                            <div class="font-semibold {{ $isOverspent ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                @if ($isOverspent)
                                    {{ __('Overspent by') }} {{ number_format(abs($totalRemaining), 2, ',', ' ') }} €
                                @else
                                    {{ __('Remaining') }}: {{ number_format($totalRemaining, 2, ',', ' ') }} €
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/20 border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-2">
                        <flux:icon.information-circle class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('No budgets defined for this month') }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Navigation du mois (1/4) -->
        <div class="lg:col-span-1">
            <div class="bg-custom-accent rounded-lg p-4 shadow-sm">
                <div class="flex flex-col items-center gap-3">
                    <div class="flex items-center gap-2">
                        <button wire:click="prevMonth" type="button"
                            class="p-2 rounded-lg border-grey-accent hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors duration-150"
                            aria-label="{{ __('Previous month') }}">
                            <flux:icon.chevron-left class="w-4 h-4 text-custom" />
                        </button>

                        <button wire:click="nextMonth" type="button"
                            class="p-2 rounded-lg border-grey-accent hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors duration-150"
                            aria-label="{{ __('Next month') }}">
                            <flux:icon.chevron-right class="w-4 h-4 text-custom" />
                        </button>
                    </div>

                    <div class="font-semibold text-custom text-center text-sm">
                        {{ $monthLabel ?? '' }}
                    </div>

                    @if ($month !== now()->format('Y-m'))
                        <button wire:click="goToCurrentMonth" type="button"
                            class="px-3 py-1 rounded-lg bg-color text-white hover:opacity-90 transition-opacity duration-150 font-medium text-sm">
                            {{ __('Today') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau desktop -->
    <div class="hidden md:block rounded-lg overflow-hidden border border-grey-accent shadow-sm hover:shadow-md transition-shadow">
        <div class="">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="sticky top-0 z-10 bg-custom">
                    <tr>
                        <th wire:click="sortBy('name')" class="px-6 py-4 text-left text-xs font-medium text-grey-inverse uppercase tracking-wider cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors duration-150"
                            aria-sort="{{ $sortField === 'name' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                            role="button" tabindex="0">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('Category') }}</span>
                                @if ($sortField === 'name')
                                    <flux:icon.chevron-up class="w-3 h-3 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('budget')" class="px-6 py-4 text-right text-xs font-medium text-grey-inverse uppercase tracking-wider cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors duration-150"
                            aria-sort="{{ $sortField === 'budget' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                            role="button" tabindex="0">
                            <div class="flex items-center justify-end space-x-1">
                                <span>{{ __('Budget') }}</span>
                                @if ($sortField === 'budget')
                                    <flux:icon.chevron-up class="w-3 h-3 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('spent')" class="px-6 py-4 text-right text-xs font-medium text-grey-inverse uppercase tracking-wider cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors duration-150"
                            aria-sort="{{ $sortField === 'spent' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                            role="button" tabindex="0">
                            <div class="flex items-center justify-end space-x-1">
                                <span>{{ __('Spent') }}</span>
                                @if ($sortField === 'spent')
                                    <flux:icon.chevron-up class="w-3 h-3 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('remaining')" class="px-6 py-4 text-right text-xs font-medium text-grey-inverse uppercase tracking-wider cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors duration-150"
                            aria-sort="{{ $sortField === 'remaining' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                            role="button" tabindex="0">
                            <div class="flex items-center justify-end space-x-1">
                                <span>{{ __('Remaining') }}</span>
                                @if ($sortField === 'remaining')
                                    <flux:icon.chevron-up class="w-3 h-3 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" />
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-medium text-grey-inverse uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-custom-accent divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($rows as $row)
                        <tr class="hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors duration-150 {{ $row['overspent'] ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full border border-zinc-300 dark:border-zinc-600"
                                         style="background-color: {{ $row['category']->color ?? '#888888' }}"></div>
                                    <div class="text-sm font-medium text-custom">
                                        {{ $row['category']->name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                @if ($row['budget'] === null)
                                    <span class="text-sm text-grey-inverse">—</span>
                                @else
                                    <span class="text-sm font-medium text-custom">
                                        {{ number_format($row['budget'], 2, ',', ' ') }} €
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-medium text-custom">
                                    {{ number_format($row['spent'], 2, ',', ' ') }} €
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                @if ($row['remaining'] === null)
                                    <span class="text-sm text-grey-inverse">—</span>
                                @else
                                    <span class="text-sm font-medium {{ $row['overspent'] ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ number_format($row['remaining'], 2, ',', ' ') }} €
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($row['budget'] !== null)
                                    @if ($row['overspent'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            <flux:icon.exclamation-triangle class="w-3 h-3 mr-1" />
                                            {{ __('Overspent') }}
                                        </span>
                                    @elseif ($row['remaining'] > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                            <flux:icon.check-circle class="w-3 h-3 mr-1" />
                                            {{ __('On track') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                            <flux:icon.exclamation-circle class="w-3 h-3 mr-1" />
                                            {{ __('At limit') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                        {{ __('No budget') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Version mobile -->
    <div class="block md:hidden">
        <div class="space-y-4">
            @foreach ($rows as $row)
                <div class="bg-custom-accent rounded-lg p-4 border border-grey-accent {{ $row['overspent'] ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20' : '' }}">
                    <!-- Header de la carte -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full border border-zinc-300 dark:border-zinc-600"
                                 style="background-color: {{ $row['category']->color ?? '#888888' }}"></div>
                            <h4 class="font-medium text-custom">{{ $row['category']->name }}</h4>
                        </div>

                        <!-- Status badge -->
                        @if ($row['budget'] !== null)
                            @if ($row['overspent'])
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                    <flux:icon.exclamation-triangle class="w-3 h-3 mr-1" />
                                    {{ __('Overspent') }}
                                </span>
                            @elseif ($row['remaining'] > 0)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                    <flux:icon.check-circle class="w-3 h-3 mr-1" />
                                    {{ __('On track') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                    <flux:icon.exclamation-circle class="w-3 h-3 mr-1" />
                                    {{ __('At limit') }}
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                {{ __('No budget') }}
                            </span>
                        @endif
                    </div>

                    <!-- Contenu de la carte -->
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div class="text-center">
                            <div class="text-grey-inverse mb-1">{{ __('Budget') }}</div>
                            <div class="font-medium text-custom">
                                @if ($row['budget'] === null)
                                    —
                                @else
                                    {{ number_format($row['budget'], 2, ',', ' ') }} €
                                @endif
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-grey-inverse mb-1">{{ __('Spent') }}</div>
                            <div class="font-medium text-custom">
                                {{ number_format($row['spent'], 2, ',', ' ') }} €
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-grey-inverse mb-1">{{ __('Remaining') }}</div>
                            <div class="font-medium {{ $row['overspent'] ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                @if ($row['remaining'] === null)
                                    —
                                @else
                                    {{ number_format($row['remaining'], 2, ',', ' ') }} €
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Barre de progression si budget défini -->
                    @if ($row['budget'] !== null && $row['budget'] > 0)
                        @php
                            // Calcul du pourcentage de progression
                            $spentAmount = abs($row['spent']); // Valeur absolue pour éviter les négatifs
                            $budgetAmount = $row['budget'];
                            $percentage = min(($spentAmount / $budgetAmount) * 100, 100);

                            // Détermination de la couleur de la barre
                            $barColor = 'bg-emerald-500';
                            if ($row['overspent']) {
                                $barColor = 'bg-red-500';
                            } elseif ($row['remaining'] <= 0) {
                                $barColor = 'bg-yellow-500';
                            }

                            // Texte du pourcentage (toujours positif)
                            $percentageText = number_format($percentage, 0) . '%';
                        @endphp
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-grey-inverse mb-1">
                                <span>{{ __('Progress') }}</span>
                                <span>{{ $percentageText }}</span>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $barColor }}"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
