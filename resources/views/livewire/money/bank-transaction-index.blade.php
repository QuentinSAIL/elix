<div class="bg-custom p-6 shadow-lg">

    <h3 class="text-xl font-semibold text-custom-inverse mb-4">Sélectionnez un compte</h3>
    <select wire:change="updateSelectedAccount($event.target.value)"
        class="w-full p-3 border border-zinc-300 dark:border-zinc-700 rounded-lg
                   bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-50
                   focus:outline-none focus:ring-2 focus:ring-accent">
        <option value="">-- Sélectionnez un compte --</option>
        @foreach ($accounts as $account)
            <option value="{{ $account->id }}">{{ $account->name }}</option>
        @endforeach
    </select>

    @if ($selectedAccount)
        <div class="">
            <h4 class="text-lg font-medium text-elix mb-3 mt-6">
                Transactions pour {{ $selectedAccount->name }}
            </h4>

            {{-- container scrollable --}}
            <div class="max-h-[71vh] overflow-y-auto" wire:scroll.debounce.200ms="loadMore">
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($transactions as $transaction)
                        <li class="py-2 flex justify-between items-center hover-custom">
                            <span class="text-custom-inverse">
                                {{ $transaction->description }}
                            </span>
                            <span class="text-sm text-zinc-500">
                                {{ $transaction->transaction_date?->format('d/m/Y') }}
                            </span>
                            <span class="text-sm text-zinc-500">
                                {{ $transaction->category?->name }}
                            </span>
                            <span class="font-semibold"
                                style="color: {{ $transaction->amount < 0 ? 'red' : 'green' }};">
                                {{ number_format($transaction->amount, 2) }} €
                            </span>
                        </li>
                    @endforeach
                </ul>

                <div wire:loading class="text-center py-4 text-sm text-zinc-500">
                    Chargement…
                </div>
            </div>
        </div>
    @endif
</div>
