<div class="p-6 shadow-md">
    <h3 class="text-xl font-semibold custom mb-4">
        Gestion des catégories
    </h3>
    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
        <thead class="bg-custom text-left">
            <tr class="bg-custom">
                <th class="px-4 py-2 sticky top-0 z-10 w-16"></th>
                <th wire:click="sortBy('name')" class="px-4 py-2 cursor-pointer sticky top-0 z-10">
                    Nom
                    @if ($sortField === 'name')
                        <span class="ml-2">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('budget')" class="px-4 py-2 cursor-pointer sticky top-0 z-10 text-right">
                    Budget
                    @if ($sortField === 'budget')
                        <span class="ml-2">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 sticky top-0 z-10 w-8 text-right">
                Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-custom-accent divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach ($categories as $category)
                <tr wire:key="cat-{{ $category->id }}">
                    <td class="px-4 py-2">
                        <input type="color" class="w-8 h-8  m-2 rounded " value="{{ $category->color }}"
                            wire:change="updateCategoryColor($event.target.value, '{{ $category->id }}')" />
                    </td>
                    <td class="px-4 py-2">
                        <input type="text"
                            class="w-full px-2 py-1 border-transparent focus:bord bg-custom-accent outline-none"
                            value="{{ $category->name }}"
                            wire:change="updateCategoryName($event.target.value, '{{ $category->id }}')" />
                    </td>
                    <td class="px-4 py-2 text-right">
                        <input type="number"
                            class="w-full px-2 py-1 text-right border-transparent bg-custom-accent outline-none"
                            value="{{ number_format($category->budget, 2, '.', '') }}"
                            wire:change="updateCategoryBudget($event.target.value, '{{ $category->id }}')" />
                    </td>
                    <td class="">
                        <div class="flex items-center justify-center">
                            <livewire:money.category-form :category="$category" wire:key="category-form-{{ $category->id }}"
                                :edition="true" />
                        <button wire:click="deleteCategory('{{ $category->id }}')"
                            class="text-red-500 hover:text-red-700 cursor-pointer focus:outline-none ml-2">
                            <flux:icon.trash class="w-5 h-5" variant="micro" />
                        </button>
                        </div>
                    </td>
                </tr>
            @endforeach

            <tr class="">
                <td class="px-4 py-2">
                    <input type="color" wire:model.defer="newColor" class="w-8 h-8 p-0 rounded " />
                </td>
                <td class="px-4 py-2">
                    <flux:input type="text" wire:model.defer="newName" placeholder="Nouvelle catégorie" />
                </td>
                <td class="px-4 py-2">
                    <flux:input type="number" wire:model.defer="newBudget" placeholder="0.00" step="1" class="" />
                </td>
                <td class="px-4 py-2 text-center">
                    <flux:button wire:click="addCategory" wire:keydown.enter="addCategory" variant="primary">
                        Ajouter
                    </flux:button>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="bg-custom">
                <td colspan="2" class="px-4 py-2 font-medium text-left custom">Total</td>
                <td class="px-4 py-2 font-medium text-right custom">
                    {{ number_format($categories->sum('budget'), 2, ',', ' ') }} €</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
