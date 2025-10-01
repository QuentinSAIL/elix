<div class="space-y-6">
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
            <button wire:click="prevMonth" type="button" class="px-2 py-1 border rounded">←</button>
            <button wire:click="goToCurrentMonth" type="button" class="px-2 py-1 border rounded">{{ __('Today') }}</button>
            <button wire:click="nextMonth" type="button" class="px-2 py-1 border rounded">→</button>
        </div>
        <div class="font-medium min-w-[10rem]">
            {{ $monthLabel ?? '' }}
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left">
                    <th class="px-3 py-2">{{ __('Category') }}</th>
                    <th class="px-3 py-2">{{ __('Budget') }}</th>
                    <th class="px-3 py-2">{{ __('Spent') }}</th>
                    <th class="px-3 py-2">{{ __('Remaining') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-t {{ $row['overspent'] ? 'bg-red-50' : '' }}">
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-block w-3 h-3 rounded" style="background: {{ $row['category']->color ?? '#888888' }}"></span>
                                {{ $row['category']->name }}
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            @if ($row['budget'] === null)
                                <span class="text-gray-400">—</span>
                            @else
                                {{ number_format($row['budget'], 2, ',', ' ') }} €
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ number_format($row['spent'], 2, ',', ' ') }} €</td>
                        <td class="px-3 py-2">
                            @if ($row['remaining'] === null)
                                <span class="text-gray-400">—</span>
                            @else
                                <span class="font-medium {{ $row['overspent'] ? 'text-red-600' : 'text-emerald-700' }}">
                                    {{ number_format($row['remaining'], 2, ',', ' ') }} €
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
