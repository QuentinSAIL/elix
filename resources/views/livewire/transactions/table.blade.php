<div>

    <div class="w-full bg-zinc-900 p-4 space-y-4 rounded-2xl text-zinc-300">
        <div class="flex items-center justify-between">
            <div>searchbar</div>
            <div>filter</div>
        </div>

        <div class="grid grid-cols-12 bg-zinc-800 px-4 py-3 rounded-2xl" id="table-header">
            <div class="col-span-8">Transaction</div>
            <div class="col-span-2">Catégorie</div>
            <div class="col-span-2 text-end">Montant</div>
        </div>

        <div class="space-y-6" id="day-transactions-list">
            @foreach ($transactions as $day)
                <div class="bg-zinc-800 p-1 rounded-2xl" id="day-table">
                    <div class="flex items-center justify-between mx-6 my-3" id="day-header">
                        <div>{{$day['date']}}</div>
                        <div class="{{ $day['total'] > 0 ? 'text-green-500' : 'text-red-500' }}">@euro($day['total'])</div>
                    </div>
                    <div class="bg-zinc-900 px-4 py-2 rounded-2xl" id="day-transactions">
                        @foreach ($day['transactions'] as $transaction)
                            <div class="p-4 border-b border-zinc-700 grid grid-cols-12" id="transaction">
                                <div class="col-span-8">{{$transaction->description}}</div>
                                <div class="col-span-2">categorie 1</div>
                                <div class="col-span-2 text-end {{ $transaction->amount > 0 ? 'text-green-500' : 'text-red-500' }}">@euro($transaction->amount)</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            <div>pagination</div>
        </div>
    </div>




</div>
