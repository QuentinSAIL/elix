<div class="p-6 rounded-lg flex flex-col h-full">
    {{-- ====== MOBILE ( < md ) ====== --}}
    <div class="md:hidden flex-1 min-h-0 flex flex-col">
        {{-- Header mobile --}}
        <div class="flex flex-col">
            <h3 class="text-2xl font-semibold">
                {{ __('Bank transactions') }}
            </h3>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mt-4">
                <div class="relative sm:w-72">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <flux:icon.magnifying-glass class="h-5 w-5 text-grey" aria-hidden="true" />
                    </span>
                    <input wire:model.live.debounce.500ms="search"
                           x-on:click.stop
                           x-on:input="$wire.isAccountLoading = true"
                           placeholder="{{ __('Search...') }}"
                        class="pl-10 pr-4 py-2 input-neutral border rounded-lg w-full text-sm"
                        aria-label="{{ __('Search transactions') }}" />
                </div>

                <div class="mt-2 sm:mt-0">
                    <flux:button wire:click="getTransactions" variant="primary">
                        {{ __('Refresh') }}
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Comptes (pills scrollables) mobile --}}
        <div class="mt-6 border-b border-zinc-200 dark:border-zinc-700 overflow-x-auto pb-1" x-data="{ isLoading: false, selectedId: @entangle('selectedAccountId'), all: @entangle('allAccounts') }"
            @account-changing.window="isLoading = true" @account-changed.window="isLoading = false">
            <nav class="flex space-x-3 w-max" wire:ignore>
                <button type="button"
                    @click="all = true; selectedId = null; $dispatch('account-changing'); $wire.updateSelectedAccount('all')"
                    class="px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-colors"
                    :class="all ? 'selected' : ''" :disabled="isLoading">
                    {{ __('All accounts') }}
                    <span class="ml-1 text-xs font-normal text-grey-inverse">
                        ({{ $user->bank_transactions_count ?? $user->bankTransactions()->count() }})
                    </span>
                    {{-- <span x-show="isLoading" class="ml-1">
                        <svg class="animate-spin h-3 w-3 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span> --}}
                </button>

                @foreach ($accounts as $acct)
                    <button type="button"
                    class="cursor-pointer px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-colors flex items-center"
                        @click="all = false; selectedId = '{{ $acct->id }}'; $dispatch('account-changing'); $wire.updateSelectedAccount('{{ $acct->id }}')"
                        :class="!all && selectedId === '{{ $acct->id }}' ? 'selected' : ''" :disabled="isLoading">
                        @if ($acct->logo)
                            <img src="{{ $acct->logo }}" alt="{{ $acct->name }}" class="w-4 h-4 mr-2">
                        @endif
                        {{ $acct->name }}
                        <span class="ml-1 text-xs font-normal text-grey-inverse">
                            ({{ $acct->transactions_count ?? $acct->transactions()->count() }})
                        </span>
                        {{-- <span x-show="isLoading" class="ml-1">
                            <svg class="animate-spin h-3 w-3 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span> --}}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Résumé + Filtres mobile --}}
        <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h4 class="text-lg font-medium">
                    {{ $selectedAccount->name ?? __('All bank accounts') }}
                </h4>
                <p class="mt-1 text-sm text-grey-inverse">
                    {{ __(':countDisplayed transactions shown out of :countTotal total, Balance:', ['countDisplayed' => count($transactions), 'countTotal' => $allAccounts ? ($user->bank_transactions_count ?? $user->bankTransactions()->count()) : ($selectedAccount ? ($selectedAccount->transactions_count ?? $selectedAccount->transactions()->count()) : 0)]) }}
                    <span class="font-medium">
                        {{ number_format($selectedAccount->balance ?? $user->sumBalances(), 2, ',', ' ') }} €
                    </span>
                </p>
            </div>

            <div class="mt-3 md:mt-0 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                <select wire:model.live="categoryFilter"
                        x-on:change="$wire.isAccountLoading = true"
                        class="px-3 py-2 rounded-lg text-sm bg-custom-accent"
                    aria-label="{{ __('Filter by category') }}">
                    <option value="">{{ __('All categories') }}</option>
                    <option value="uncategorized">{{ __('Uncategorized') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="dateFilter"
                        x-on:change="$wire.isAccountLoading = true"
                        class="px-3 py-2 rounded-lg text-sm bg-custom-accent"
                    aria-label="{{ __('Filter by date') }}">
                    <option value="all">{{ __('All dates') }}</option>
                    <option value="current_month">{{ __('Current month') }}</option>
                    <option value="last_month">{{ __('Last month') }}</option>
                    <option value="current_year">{{ __('Current year') }}</option>
                </select>
            </div>
        </div>

        {{-- Liste mobile avec cartes --}}
        <div class="mt-4 flex-1 min-h-0 overflow-y-auto relative" x-data="{
            fetchLoading: false,
            accountLoading: @entangle('isAccountLoading'),
            loadMore() {
                if (this.fetchLoading || $wire.noMoreToLoad) return;
                this.fetchLoading = true;
                $wire.loadMore().then(() => {
                    this.fetchLoading = false;
                });
            }
        }" x-init="$el.scrollTop = 0;
        $el.addEventListener('scroll', () => {
            if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100) {
                loadMore();
            }
        })"
            @account-changing.window="accountLoading = true" @account-changed.window="accountLoading = false">
            <div class="absolute inset-0 bg-black/10 dark:bg-white/5 backdrop-blur-sm flex items-center justify-center z-10"
                x-show="accountLoading" x-cloak>
                <svg class="animate-spin h-6 w-6 text-color" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
            <div :class="accountLoading ? 'blur-sm pointer-events-none select-none' : ''">
                @forelse($transactions as $tx)
                    <div class="bg-custom-accent rounded-lg p-4 mb-3 border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 pr-3">
                                {{-- Compte (si All) --}}
                                @if ($allAccounts)
                                    <div class="flex items-center text-xs mb-2 text-grey-inverse">
                                        @if ($tx->account->logo)
                                            <img src="{{ $tx->account->logo }}" alt="{{ $tx->account->name }} logo"
                                                class="w-4 h-4 mr-2">
                                        @endif
                                        <span class="truncate">{{ $tx->account->name }}</span>
                                    </div>
                                @endif

                                <input
                                    type="text"
                                    class="text-sm font-medium mb-1 input-none w-full bg-transparent focus:outline-none"
                                    value="{{ $tx->description }}"
                                    wire:change="updateTransactionDescription('{{ $tx->id }}', $event.target.value)"
                                    aria-label="{{ __('Transaction description') }}"
                                />

                                <div class="text-xs text-grey-inverse mb-2">
                                    {{ $tx->transaction_date->format('d/m/Y') }}
                                </div>

                                <div>
                                    <livewire:money.category-select mobile
                                        wire:key="m-transaction-form-{{ $tx->id }}-{{ $loop->index }}"
                                        :category="$tx->category ?? null" :transaction="$tx" />
                                </div>
                            </div>

                            <div class="text-right">
                                <div
                                    class="text-lg font-semibold {{ $tx->amount < 0 ? 'text-red-500' : 'text-green-500' }}">
                                    {{ number_format($tx->amount, 2, ',', ' ') }} €
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-grey">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-grey" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="mt-2">{{ __('No transactions found.') }}</p>
                            @if ($search)
                                <p class="mt-1 text-grey">{{ __('Try changing your search criteria.') }}</p>
                            @endif
                        </div>
                    </div>
                @endforelse

                {{-- Loader mobile (pagination) --}}
                <div class="px-4 py-3 text-center" x-show="fetchLoading">
                    <div class="text-sm text-grey-accent flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-color" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        {{ __('Loading transactions...') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== DESKTOP ( ≥ md ) ====== --}}
    <div class="hidden md:block">
        {{-- Header desktop --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <h3 class="text-2xl font-semibold">
                {{ __('Bank transactions') }}
            </h3>

            <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-3 mt-4 md:mt-0">
                <div class="relative flex-grow">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <flux:icon.magnifying-glass class="h-5 w-5 text-grey" aria-hidden="true" />
                    </span>

                    <input wire:model.live.debounce.500ms="search"
                           x-on:click.stop
                           x-on:input="$wire.isAccountLoading = true"
                        placeholder="{{ __('Search...') }}"
                        class="pl-10 pr-4 py-2 input-neutral border rounded-lg w-full md:w-64 text-sm"
                        aria-label="{{ __('Search transactions') }}" />
                </div>

                <flux:button wire:click="getTransactions" variant="primary" class="cursor-pointer">
                    {{ __('Refresh') }}
                </flux:button>
            </div>
        </div>

        {{-- Comptes (onglets) desktop --}}
        <div class="mt-6 border-b border-zinc-200 dark:border-zinc-700 overflow-x-auto pb-1" x-data="{ isLoading: false, selectedId: @entangle('selectedAccountId'), all: @entangle('allAccounts') }"
            @account-changing.window="isLoading = true" @account-changed.window="isLoading = false">
            <nav class="flex space-x-4" wire:ignore>
                <button type="button"
                    @click="all = true; selectedId = null; $dispatch('account-changing'); $wire.updateSelectedAccount('all')"
                    class="cursor-pointer px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-colors"
                    :class="all ? 'selected' : ''" :disabled="isLoading">
                    {{ __('All accounts') }}
                    <span class="ml-1 text-xs font-normal text-grey-inverse">
                        ({{ $user->bank_transactions_count ?? $user->bankTransactions()->count() }})
                    </span>
                    {{-- <span x-show="isLoading" class="ml-1">
                        <svg class="animate-spin h-3 w-3 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span> --}}
                </button>

                @foreach ($accounts as $acct)
                    <button type="button"
                    class="cursor-pointer px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-colors flex items-center"
                        @click="all = false; selectedId = '{{ $acct->id }}'; $dispatch('account-changing'); $wire.updateSelectedAccount('{{ $acct->id }}')"
                        :class="!all && selectedId === '{{ $acct->id }}' ? 'selected' : ''"
                        :disabled="isLoading">
                        @if ($acct->logo)
                            <img src="{{ $acct->logo }}" alt="{{ $acct->name }}" class="w-4 h-4 mr-2">
                        @endif
                        {{ $acct->name }}
                        <span class="cursor-pointer ml-1 text-xs font-normal text-grey-inverse">
                            ({{ $acct->transactions_count ?? $acct->transactions()->count() }})
                        </span>
                        {{-- <span x-show="isLoading" class="ml-1">
                            <svg class="animate-spin h-3 w-3 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span> --}}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Résumé + Filtres desktop --}}
        <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h4 class="text-lg font-medium">
                    {{ $selectedAccount->name ?? __('All bank accounts') }}
                </h4>
                <p class="mt-1 text-sm text-grey-inverse">
                    {{ __(':countDisplayed transactions shown out of :countTotal total, Balance:', ['countDisplayed' => count($transactions), 'countTotal' => $allAccounts ? ($user->bank_transactions_count ?? $user->bankTransactions()->count()) : ($selectedAccount ? ($selectedAccount->transactions_count ?? $selectedAccount->transactions()->count()) : 0)]) }}
                    <span class="font-medium">
                        {{ number_format($selectedAccount->balance ?? $user->sumBalances(), 2, ',', ' ') }}
                        €</span>
                </p>
            </div>

            <div class="mt-3 md:mt-0 flex space-x-2">
                <select wire:model.live="categoryFilter"
                        x-on:change="$wire.isAccountLoading = true"
                        class="cursor-pointer px-3 py-2 rounded-lg text-sm bg-custom-accent"
                    aria-label="{{ __('Filter by category') }}">
                    <option value="">{{ __('All categories') }}</option>
                    <option value="uncategorized">{{ __('Uncategorized') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="dateFilter"
                        x-on:change="$wire.isAccountLoading = true"
                        class="cursor-pointer px-3 py-2 rounded-lg text-sm bg-custom-accent"
                    aria-label="{{ __('Filter by date') }}">
                    <option value="all">{{ __('All dates') }}</option>
                    <option value="current_month">{{ __('Current month') }}</option>
                    <option value="last_month">{{ __('Last month') }}</option>
                    <option value="current_year">{{ __('Current year') }}</option>
                </select>
            </div>
        </div>

        {{-- Tableau desktop avec scroll --}}
        <div x-data="{
            fetchLoading: false,
            accountLoading: @entangle('isAccountLoading'),
            loadMore() {
                if (this.fetchLoading || $wire.noMoreToLoad) return;
                this.fetchLoading = true;
                $wire.loadMore().then(() => {
                    this.fetchLoading = false;
                });
            }
        }" x-init="$el.scrollTop = 0;
        $el.addEventListener('scroll', () => {
            if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100) {
                loadMore();
            }
        })" @account-changing.window="accountLoading = true"
            @account-changed.window="accountLoading = false"
            class="mt-4 max-h-[70vh] overflow-y-auto overflow-x-auto bg-custom rounded-lg relative">
            <div class="absolute inset-0 bg-black/5 dark:bg-white/5 backdrop-blur-sm flex items-center justify-center z-10"
                x-show="accountLoading" x-cloak>
                <svg class="animate-spin h-6 w-6 text-color" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
            <div :class="accountLoading ? 'blur-sm pointer-events-none select-none' : ''">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="sticky top-0 z-10 bg-custom shadow-sm">
                            <tr>
                                @if ($allAccounts)
                                    <th wire:click="sortBy('bank_account_id')"
                                        class="px-4 py-3 text-left text-xs font-medium w-32 cursor-pointer"
                                        aria-sort="{{ $sortField === 'bank_account_id' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                        role="button" tabindex="0">
                                        <div class="flex items-center">
                                            <span>{{ __('Account') }}</span>
                                            @if ($sortField === 'bank_account_id')
                                                <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"
                                                    aria-hidden="true">
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
                                    class="px-4 py-3 text-left text-xs font-medium w-3/5 cursor-pointer"
                                    aria-sort="{{ $sortField === 'description' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                    role="button" tabindex="0">
                                    <div class="flex items-center">
                                        <span>{{ __('Description') }}</span>
                                        @if ($sortField === 'description')
                                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
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
                                    class="px-4 py-3 text-center text-xs font-medium w-1/12 cursor-pointer"
                                    aria-sort="{{ $sortField === 'transaction_date' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                    role="button" tabindex="0">
                                    <div class="flex items-center justify-center">
                                        <span>{{ __('Date') }}</span>
                                        @if ($sortField === 'transaction_date')
                                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
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
                                    class="px-4 py-3 text-center text-xs font-medium w-1/5 cursor-pointer"
                                    aria-sort="{{ $sortField === 'money_category_id' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                    role="button" tabindex="0">
                                    <div class="flex items-center justify-center">
                                        <span>{{ __('Category') }}</span>
                                        @if ($sortField === 'money_category_id')
                                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
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
                                    class="px-4 py-3 text-right text-xs font-medium w-1/12 cursor-pointer"
                                    aria-sort="{{ $sortField === 'amount' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                    role="button" tabindex="0">
                                    <div class="flex items-center justify-end">
                                        <span>{{ __('Amount') }}</span>
                                        @if ($sortField === 'amount')
                                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
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
                                                    <img src="{{ $tx->account->logo }}"
                                                        alt="{{ $tx->account->name }} logo"
                                                        class="w-5 h-5 mr-2 flex-shrink-0">
                                                @endif
                                                <span class="truncate max-w-[120px]">{{ $tx->account->name }}</span>
                                            </div>
                                        </td>
                                    @endif

                                    <td class="px-4 py-3 text-sm">
                                        <input
                                            type="text"
                                            class="input-none w-full bg-transparent focus:outline-none"
                                            value="{{ $tx->description }}"
                                            wire:change="updateTransactionDescription('{{ $tx->id }}', $event.target.value)"
                                            aria-label="{{ __('Transaction description') }}"
                                        />
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
                                    <td colspan="{{ $allAccounts ? 5 : 4 }}"
                                        class="px-4 py-8 text-center text-sm text-grey" role="status">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-12 w-12 text-grey" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <p class="mt-2">{{ __('No transactions found.') }}</p>
                                            @if ($search)
                                                <p class="mt-1 text-grey">
                                                    {{ __('Try changing your search criteria.') }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                {{-- Loader desktop (pagination) --}}
                <div class="bg-custom-ultra rounded-none px-4 py-3 border-t text-center" role="status"
                    x-show="fetchLoading">
                    <div class="text-sm text-grey-accent flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-color" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        {{ __('Loading transactions...') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
