<div class="p-6 bg-white dark:bg-zinc-800 rounded-lg shadow-md">
    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">
        Sélectionnez un compte
    </h3>

    <div class="mt-4 flex items-center space-x-3">
        <select wire:change="updateSelectedAccount($event.target.value)" wire:model="selectedAccountId"
            class="flex-1 px-4 py-2 border rounded-lg bg-zinc-50 dark:bg-zinc-700">
            <option value="">-- Sélectionnez --</option>
            @foreach ($accounts as $acct)
                <option value="{{ $acct->id }}">{{ $acct->name }}</option>
            @endforeach
        </select>

        <flux:button wire:click="refreshTransaction" variant="primary">
            Rafraîchir
        </flux:button>
    </div>

    @if ($selectedAccount)
        <h4 class="mt-6 text-lg font-medium text-zinc-900 dark:text-zinc-50">
            Transactions pour « {{ $selectedAccount->name }} »
        </h4>
        <div class="mt-4 max-h-[70vh] overflow-y-auto" wire:scroll.debounce.200ms="loadMore">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700 text-left">
                    <tr>
                        <th wire:click="sortBy('description')"
                            class="px-4 py-2 cursor-pointer sticky top-0 z-10 bg-zinc-50 dark:bg-zinc-700">
                            <span class="mx-2">Description</span>

                            @if ($sortField === 'description')
                                @if ($sortDirection === 'asc')
                                    ↑
                                @else
                                    ↓
                                @endif
                            @endif

                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher..."
                                class="px-4 py-2 border rounded-lg flex-1" onclick="event.stopPropagation()" />

                        </th>
                        <th wire:click="sortBy('transaction_date')"
                            class="px-4 py-2 cursor-pointer sticky top-0 z-10 bg-zinc-50 dark:bg-zinc-700">
                            Date
                            @if ($sortField === 'transaction_date')
                                @if ($sortDirection === 'asc')
                                    ↑
                                @else
                                    ↓
                                @endif
                            @endif
                        </th>
                        <th wire:click="sortBy('category_id')"
                            class="px-4 py-2 cursor-pointer sticky top-0 z-10 bg-zinc-50 dark:bg-zinc-700">
                            Catégorie
                            @if ($sortField === 'category_id')
                                @if ($sortDirection === 'asc')
                                    ↑
                                @else
                                    ↓
                                @endif
                            @endif
                        </th>
                        <th class="px-4 py-2 sticky top-0 z-10 bg-zinc-50 dark:bg-zinc-700">
                            Actions
                        </th>
                        <th wire:click="sortBy('amount')"
                            class="px-4 py-2 text-right cursor-pointer sticky top-0 z-10 bg-zinc-50 dark:bg-zinc-700">
                            Montant
                            @if ($sortField === 'amount')
                                @if ($sortDirection === 'asc')
                                    ↑
                                @else
                                    ↓
                                @endif
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                            <td class="px-4 py-2 text-sm">{{ $tx->description }}</td>
                            <td class="px-4 py-2 text-xs">{{ $tx->transaction_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-xs">{{ $tx->category->name ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <flux:button wire:click="" variant="primary" class="p-1">
                                    <flux:icon name="tag" variant="micro" />
                                </flux:button>
                            </td>
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
                </tbody>
            </table>
            <div wire:loading class="py-4 text-center text-sm text-zinc-500">
                Chargement…
            </div>
        </div>
    @endif
</div>
