<div class="p-6 rounded-lg">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <h3 class="text-2xl font-semibold">
            Transactions bancaires
        </h3>

        <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-3 mt-4 md:mt-0">
            <div class="relative flex-grow">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <flux:icon.magnifying-glass class="h-5 w-5 text-grey" />
                </span>

                <input wire:model.live.debounce.500ms="search" x-on:click.stop placeholder="Rechercher..."
                    class="pl-10 pr-4 py-2 input-neutral border rounded-lg w-full md:w-64 text-sm" aria-label="{{ __('Search transactions') }}" />
            </div>

            <flux:button wire:click="getTransactions" variant="primary" class="">
                {{ __('Actualiser') }}
            </flux:button>
        </div>
    </div>

    <div class="mt-6 border-b border-zinc-200 dark:border-zinc-700 overflow-x-auto pb-1">
        <nav class="flex space-x-4">
            <button wire:click="updateSelectedAccount('all')"
                class="px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-colors
                    {{ $allAccounts ? 'selected' : '' }}">
                Tous les comptes
                <span class="ml-1 text-xs font-normal text-grey-inverse">
                    ({{ $user->bankTransactions()->count() }})
                </span>
            </button>

            @foreach ($accounts as $acct)
                <button wire:click="updateSelectedAccount('{{ $acct->id }}')"
                    class="px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-colors flex items-center
                        {{ $selectedAccount && $selectedAccount->id === $acct->id ? 'selected' : '' }}">
                    @if ($acct->logo)
                        <img src="{{ $acct->logo }}" alt="{{ $acct->name }}" class="w-4 h-4 mr-2">
                    @endif
                    {{ $acct->name }}
                    <span class="ml-1 text-xs font-normal text-grey-inverse">
                        ({{ $acct->transactions()->count() }})
                    </span>
                </button>
            @endforeach
        </nav>
    </div>

    <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h4 class="text-lg font-medium">
                {{ $selectedAccount->name ?? 'Tous les comptes bancaires' }}
            </h4>
            <p class="mt-1 text-sm text-grey-inverse">
                {{ count($transactions) }} transactions affichées sur
                {{ count($selectedAccount->transactions ?? $user->bankTransactions()->get()) }} au total,
                Solde : <span class="font-medium">
                    {{ number_format($selectedAccount->balance ?? $user->sumBalances(), 2, ',', ' ') }}
                    €</span>
            </p>
        </div>

        <div class="mt-3 md:mt-0 flex space-x-2">
            <select wire:model.live="categoryFilter" class="px-3 py-2 rounded-lg text-sm bg-custom-accent" aria-label="{{ __('Filter by category') }}">
                <option value="">Toutes catégories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="dateFilter" class="px-3 py-2 rounded-lg text-sm bg-custom-accent" aria-label="{{ __('Filter by date') }}">
                <option value="all">Toutes dates</option>
                <option value="current_month">Mois courant</option>
                <option value="last_month">Mois dernier</option>
                <option value="current_year">Année courante</option>
            </select>
        </div>
    </div>

    <div x-data x-init="$el.scrollTop = 0;
    $el.addEventListener('scroll', () => {
        if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 5) {
            $wire.loadMore()
        }
    })" class="mt-4 max-h-[70vh] overflow-y-auto bg-custom rounded-lg">


        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr class="sticky top-0 z-10 bg-custom shadow-sm">
                        @if ($allAccounts)
                            <th wire:click="sortBy('bank_account_id')"
                                class="px-4 py-3 text-left text-xs font-medium sticky top-0 w-32"
                                aria-sort="{{ $sortField === 'bank_account_id' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                            >
                                <div class="flex items-center">
                                    <span>Compte</span>
                                    @if ($sortField === 'bank_account_id')
                                        <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            @if ($sortDirection === 'asc')
                                                <path fill-rule="evenodd"
                                                    d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                                    clip-rule="evenodd" />
                                            @else
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                        @endif

                        <th wire:click="sortBy('description')"
                            class="px-4 py-3 text-left text-xs font-medium sticky top-0 w-3/5"
                            aria-sort="{{ $sortField === 'description' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                        >
                            <div class="flex items-center">
                                <span>Description</span>
                                @if ($sortField === 'description')
                                    <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if ($sortDirection === 'asc')
                                            <path fill-rule="evenodd"
                                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                                clip-rule="evenodd" />
                                        @else
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        @endif
                                    </svg>
                                @endif
                            </div>
                        </th>

                        <th wire:click="sortBy('transaction_date')"
                            class="px-4 py-3 text-center text-xs font-medium sticky top-0 w-1/12"
                            aria-sort="{{ $sortField === 'transaction_date' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                        >
                            <div class="flex items-center justify-center">
                                <span>Date</span>
                                @if ($sortField === 'transaction_date')
                                    <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if ($sortDirection === 'asc')
                                            <path fill-rule="evenodd"
                                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                                clip-rule="evenodd" />
                                        @else
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        @endif
                                    </svg>
                                @endif
                            </div>
                        </th>

                        <th wire:click="sortBy('money_category_id')"
                            class="px-4 py-3 text-center text-xs font-medium sticky top-0 w-1/5"
                            aria-sort="{{ $sortField === 'money_category_id' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                        >
                            <div class="flex items-center justify-center">
                                <span>Catégorie</span>
                                @if ($sortField === 'money_category_id')
                                    <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if ($sortDirection === 'asc')
                                            <path fill-rule="evenodd"
                                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                                clip-rule="evenodd" />
                                        @else
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        @endif
                                    </svg>
                                @endif
                            </div>
                        </th>

                        <th wire:click="sortBy('amount')"
                            class="px-4 py-3 text-right text-xs font-medium sticky top-0 w-1/12"
                            aria-sort="{{ $sortField === 'amount' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                        >
                            <div class="flex items-center justify-end">
                                <span>Montant</span>
                                @if ($sortField === 'amount')
                                    <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if ($sortDirection === 'asc')
                                            <path fill-rule="evenodd"
                                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                                clip-rule="evenodd" />
                                        @else
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        @endif
                                    </svg>
                                @endif
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-custom-ultra">
                    @forelse($transactions as $tx)
                        <tr class="hover transition-colors">
                            @if ($allAccounts)
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        @if ($tx->account->logo)
                                            <img src="{{ $tx->account->logo }}" alt="Logo"
                                                class="w-5 h-5 mr-2 flex-shrink-0">
                                        @endif
                                        <span class="truncate max-w-[120px]">{{ $tx->account->name }}</span>
                                    </div>
                                </td>
                            @endif

                            <td class="px-4 py-3 text-sm">
                                {{ $tx->description }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-xs text-center">
                                {{ $tx->transaction_date->format('d/m/Y') }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                <livewire:money.category-select
                                    wire:key="transaction-form-{{ $tx->id }}-{{ $loop->index }}"
                                    :category="$tx->category ?? null" :transaction="$tx" />
                            </td>

                            <td
                                class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium {{ $tx->amount < 0 ? 'text-red-500' : 'text-green-500' }}">
                                {{ number_format($tx->amount, 2, ',', ' ') }} €
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $allAccounts ? 5 : 4 }}" class="px-4 py-8 text-center text-sm text-grey" role="status">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="h-12 w-12 text-grey" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="mt-2">Aucune transaction trouvée.</p>
                                    @if ($search)
                                        <p class="mt-1 text-grey">Essayez de modifier vos critères de recherche.
                                        </p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-custom-ultra rounded-none px-4 py-3 border-t text-center" role="status">
            <div class="text-sm text-grey-accent flex items-center justify-center"><svg
                    class="animate-spin -ml-1 mr-3 h-5 w-5 text-color" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Chargement des transactions...
            </div>
        </div>
    </div>
</div>
