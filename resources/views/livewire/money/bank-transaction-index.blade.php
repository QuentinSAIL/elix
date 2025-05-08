<div class="p-6 bg-custom rounded-lg shadow-md">

    <h3 class="text-xl font-semibold custom">
        Sélectionnez un compte
    </h3>

    <div class="mt-4 flex items-center space-x-3">
        <select wire:change="updateSelectedAccount($event.target.value)"
            class="flex-1 px-4 py-2 border rounded-lg bg-custom">
            <option value="">-- Sélectionnez --</option>
            @foreach ($accounts as $acct)
                <option value="{{ $acct->id }}">{{ $acct->name }}</option>
            @endforeach
        </select>

        <flux:button wire:click="refreshTransaction" variant="primary">
            {{ __('Reload') }}
        </flux:button>
    </div>

    @if ($selectedAccount)
        <div>
            <h4 class="mt-6 text-lg font-medium custom">
                Transactions pour « {{ $selectedAccount->name }} »
            </h4>
            <p class="mt-2 text-sm text-zinc-500">
                {{ count($selectedAccount->transactions) }} transactions,
                Solde : {{ number_format($selectedAccount->balance, 2, ',', ' ') }} €
            </p>
        </div>
        <div x-data x-init="$el.addEventListener('scroll', () => {
            if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 5) {
                $wire.loadMore()
            }
        })" class="mt-4 max-h-[70vh] overflow-y-auto bg-custom-accent rounded-lg">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-custom text-left">
                    <tr>
                        <th wire:click="sortBy('description')"
                            class="px-4 py-2 cursor-pointer sticky top-0 z-10 bg-custom rounded-none border-none">
                            <span class="mx-2">Description</span>

                            @if ($sortField === 'description')
                                @if ($sortDirection === 'asc')
                                    <span class="ml-2">↑</span>
                                @else
                                    <span class="ml-2">↓</span>
                                @endif
                            @endif

                            <input wire:model.live.debounce.500ms="search" x-on:click.stop
                                placeholder="Rechercher..."
                                class="ml-8 px-4 py-2 border rounded-lg flex-1 text-md focus:outline-none" />

                        </th>
                        <th wire:click="sortBy('transaction_date')"
                            class="px-4 py-2 cursor-pointer sticky top-0 z-10 bg-custom rounded-none border-none text-center">
                            Date
                            @if ($sortField === 'transaction_date')
                                @if ($sortDirection === 'asc')
                                    <span class="ml-2">↑</span>
                                @else
                                    <span class="ml-2">↓</span>
                                @endif
                            @endif
                        </th>
                        <th wire:click="sortBy('money_category_id')"
                            class="px-4 py-2 cursor-pointer sticky top-0 z-10 bg-custom rounded-none border-none text-center">
                            Catégorie
                            @if ($sortField === 'money_category_id')
                                @if ($sortDirection === 'asc')
                                    <span class="ml-2">↑</span>
                                @else
                                    <span class="ml-2">↓</span>
                                @endif
                            @endif
                        </th>
                        <th wire:click="sortBy('amount')"
                            class="px-4 py-2 text-right cursor-pointer sticky top-0 z-10 bg-custom rounded-none border-none">
                            Montant
                            @if ($sortField === 'amount')
                                @if ($sortDirection === 'asc')
                                    <span class="ml-2">↑</span>
                                @else
                                    <span class="ml-2">↓</span>
                                @endif
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @if ($transactions)
                        @forelse($transactions as $tx)
                            <tr class="hover-custom-accent transition">
                                <td class="px-4 py-2 text-sm">
                                    {{ $tx->description }}
                                </td>
                                <td class="px-4 py-2 text-xs text-center">
                                    {{ $tx->transaction_date->format('d/m/Y') }}
                                </td>
                                @if ($tx->category)
                                    <td class="px-4 py-2 text-xs text-center">
                                        <livewire:money.category-select wire:key="transaction-form-{{ $tx->id }}-{{ $loop->index }}"
                                            :category="$tx->category" :transaction="$tx" />
                                    </td>
                                @else
                                    <td class="px-4 py-2 text-xs text-center">
                                        <livewire:money.category-select wire:key="transaction-form-{{ $tx->id }}-{{ $loop->index }}"
                                            :transaction="$tx" />
                                    </td>
                                @endif
                                <td
                                    class="px-4 py-2 text-xs text-right font-semibold {{ $tx->amount < 0 ? 'text-red-500' : 'text-green-500' }}">
                                    {{ number_format($tx->amount, 2, ',', ' ') }} €
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-sm text-zinc-500">
                                    Aucune transaction trouvée.
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
            <div wire:loading class="py-4 text-center text-sm text-zinc-500">
                Chargement…
            </div>
        </div>
    @endif
</div>
