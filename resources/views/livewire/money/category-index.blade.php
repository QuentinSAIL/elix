<div class="p-6 shadow-md rounded-lg border border-zinc-200 dark:border-zinc-700">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-xl font-semibold custom">
                Gestion des catégories
            </h3>
            <p class="text-sm text-zinc-500 mt-1">
                Gérez vos catégories de dépenses et leurs budgets associés
            </p>
        </div>
        <div class="bg-custom-accent rounded-lg p-3 flex flex-col items-end">
            <span class="text-sm text-zinc-500">Budget total</span>
            <span class="text-xl font-semibold custom">{{ number_format($categories->sum('budget'), 2, ',', ' ') }}
                €</span>
        </div>
    </div>

    <div class="rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-custom text-left">
                <tr class="bg-custom">
                    <th class="px-4 py-3 sticky top-0 z-10 w-16">Couleur</th>
                    <th wire:click="sortBy('name')" class="px-4 py-3 cursor-pointer sticky top-0 z-10 group">
                        <div class="flex items-center space-x-1">
                            <span>Nom</span>
                            @if ($sortField === 'name')
                                <span class="ml-2">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="ml-2 opacity-0 group-hover:opacity-30">↕</span>
                            @endif
                        </div>
                    </th>
                    <th wire:click="sortBy('budget')"
                        class="px-4 py-3 cursor-pointer sticky top-0 z-10 text-right group">
                        <div class="flex items-center justify-end space-x-1">
                            <span>Budget</span>
                            @if ($sortField === 'budget')
                                <span class="ml-2">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="ml-2 opacity-0 group-hover:opacity-30">↕</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-3 sticky top-0 z-10 w-28 text-center">
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody class="bg-custom-accent divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($categories as $index => $category)
                    <tr wire:key="cat-{{ $category->id }}">
                        <td class="px-4 py-3 flex justify-center">
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
                                    class="p-2 text-zinc-400 hover:text-red-500 rounded-full hover:bg-red-50 transition-colors duration-150 cursor-pointer"
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

    <div class="mt-6 p-4 rounded-lg border border-dashed border-zinc-300 bg-custom-accent bg-opacity-50">
        <h4 class="text-sm font-medium mb-3 text-zinc-500">Ajouter une nouvelle catégorie</h4>
        <div class="grid grid-cols-12 gap-3 items-center">
            <div class="col-span-1">
                <div class="flex justify-center">
                    <input type="color" wire:model.defer="newColor"
                        class="w-8 h-8 rounded cursor-pointer border border-zinc-300" />
                </div>
            </div>
            <div class="col-span-5">
                <flux:input type="text" wire:model.defer="newName" placeholder="Nom de la catégorie" />
            </div>
            <div class="col-span-4">
                <div class="relative">
                    <flux:input type="number" wire:model.defer="newBudget" placeholder="0.00" step="1" />
                </div>
            </div>
            <div class="col-span-2">
                <flux:button wire:click="addCategory" wire:keydown.enter="addCategory" variant="primary" class="w-full">
                    <span class="flex items-center justify-center">
                        <flux:icon.plus class="w-4 h-4 mr-1" />
                        Ajouter
                    </span>
                </flux:button>
            </div>
        </div>
    </div>
</div>
