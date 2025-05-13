<div class="p-6 rounded-lg">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div class="mb-8">
            <h3 class="text-2xl font-semibold">
                Gestion des catégories
            </h3>
            <p class="text-sm text-grey-inverse mt-1">
                Gérez vos catégories de dépenses et leurs budgets associés
            </p>
        </div>
        <div class="bg-custom-accent rounded-lg p-4 flex flex-col items-end shadow-sm -mt-12">
            <span class="text-sm text-grey-inverse">Budget total</span>
            <span class="text-xl font-bold custom">{{ number_format($categories->sum('budget'), 2, ',', ' ') }}
                €</span>
        </div>
    </div>

    <div
        class="rounded-lg overflow-hidden border border-grey-accent shadow-sm hover:shadow-md transition-shadow h-[60vh] overflow-y-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="text-left sticky top-0 z-10 bg-custom">
                <tr>
                    <th wire:click="sortBy('color')" class="px-4 w-30 cursor-pointer group">
                        <div class="flex items-center space-x-1">
                            <span>Couleur</span>
                            @if ($sortField === 'color')
                                <x-atoms.sort-direction :sortDirection="$sortDirection" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sortBy('name')" class="px-4 py-3 cursor-pointer group">
                        <div class="flex items-center space-x-1">
                            <span>Nom</span>
                            @if ($sortField === 'name')
                                <x-atoms.sort-direction :sortDirection="$sortDirection" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sortBy('budget')" class="px-4 py-4 cursor-pointer text-right group">
                        <div class="flex items-center justify-end space-x-1">
                            <span>Budget</span>
                            @if ($sortField === 'budget')
                                <x-atoms.sort-direction :sortDirection="$sortDirection" />
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4 w-28 text-center">
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody class="bg-custom-accent divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($categories as $index => $category)
                    <tr wire:key="cat-{{ $category->id }}" class="hover:bg-custom-accent transition-colors">
                        <td class="px-4 py-1 flex justify-center">
                            <input type="color" class="w-8 h-8 m-2 rounded cursor-pointer outline-none"
                                wire:change="updateCategoryColor($event.target.value, '{{ $category->id }}')"
                                value="{{ $category->color }}" />

                        </td>
                        <td class="px-4 py-3">
                            <input type="text"
                                class="w-full px-3 py-2 border-transparent focus:border-zinc-300 focus:ring-1 focus:ring-custom rounded-md bg-custom-accent outline-none transition-all duration-150"
                                value="{{ $category->name }}"
                                wire:change="updateCategoryName($event.target.value, '{{ $category->id }}')" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="relative">
                                <input type="number"
                                    class="w-full px-3 py-2 text-right border-transparent focus:border-zinc-300 focus:ring-1 focus:ring-custom rounded-md bg-custom-accent outline-none transition-all duration-150"
                                    value="{{ number_format($category->budget, 2, '.', '') }}"
                                    wire:change="updateCategoryBudget($event.target.value, '{{ $category->id }}')" />
                            </div>
                        </td>
                        <td class="px-2">
                            <div class="flex items-center justify-center space-x-2">
                                <livewire:money.category-form :category="$category"
                                    wire:key="category-form-{{ $category->id }}" :edition="true" />
                                <button wire:click="deleteCategory('{{ $category->id }}')"
                                    class="p-2 hover:text-danger-500 rounded-full hover:bg-danger-50 transition-colors duration-150 cursor-pointer"
                                    title="Supprimer cette catégorie">
                                    <flux:icon.trash class="w-5 h-5" variant="micro" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div
        class="mt-6 p-6 rounded-lg border-2 border-dashed border-grey-accent bg-custom-accent bg-opacity-50 shadow-sm hover:shadow-md transition-shadow">
        <h4 class="font-medium mb-6 text-grey">Ajouter une nouvelle catégorie</h4>
        <div class="grid grid-cols-12 gap-4 items-center">
            <div class="col-span-1">
                <div class="flex justify-center">
                    <input type="color" wire:model.defer="newColor"
                        class="w-10 h-10 rounded cursor-pointer border border-zinc-300" />
                </div>
            </div>
            <div class="col-span-5">
                <flux:input type="text" wire:model.defer="newName" placeholder="Nom de la catégorie"
                    class="w-full" />
            </div>
            <div class="col-span-4">
                <div class="relative">
                    <flux:input type="number" wire:model.defer="newBudget" placeholder="0.00" step="1"
                        class="w-full" />
                </div>
            </div>
            <div class="col-span-2">
                <flux:button wire:click="addCategory" wire:keydown.enter="addCategory" variant="primary"
                    class="w-full shadow-sm hover:shadow-md transition-shadow">
                    <span class="flex items-center justify-center">
                        <flux:icon.plus class="w-4 h-4 mr-1" />
                        Ajouter
                    </span>
                </flux:button>
            </div>
        </div>
    </div>
</div>
