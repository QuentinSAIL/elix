<div class="p-6 rounded-lg">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div class="mb-8">
            <h3 class="text-2xl font-semibold">
                {{ __('Manage categories') }}
            </h3>
            <p class="text-sm text-grey-inverse mt-1">
                {{ __('Manage your expense categories and budgets') }}
            </p>
        </div>
        <div class="bg-custom-accent rounded-lg p-4 flex flex-col items-end shadow-sm lg:-mt-12">
            <span class="text-sm text-grey-inverse">{{ __('Total budget') }}</span>
            <span class="text-xl font-bold custom">{{ number_format($categories->sum('budget'), 2, ',', ' ') }}
                €</span>
        </div>
    </div>

    <div class="hidden md:!block rounded-lg overflow-hidden overflow-x-auto border border-grey-accent shadow-sm hover:shadow-md transition-shadow h-[60vh] overflow-y-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="text-left sticky top-0 z-10 bg-custom">
                <tr>
                    <th wire:click="sortBy('color')" class="px-4 w-30 cursor-pointer group"
                        aria-sort="{{ $sortField === 'color' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                        role="button" tabindex="0"
                    >
                        <div class="flex items-center space-x-1">
                            <span>{{ __('Color') }}</span>
                            @if ($sortField === 'color')
                                <x-atoms.sort-direction :sortDirection="$sortDirection" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sortBy('name')" class="px-4 py-3 cursor-pointer group"
                        aria-sort="{{ $sortField === 'name' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                        role="button" tabindex="0"
                    >
                        <div class="flex items-center space-x-1">
                            <span>{{ __('Name') }}</span>
                            @if ($sortField === 'name')
                                <x-atoms.sort-direction :sortDirection="$sortDirection" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sortBy('budget')" class="px-4 py-4 cursor-pointer text-right group"
                        aria-sort="{{ $sortField === 'budget' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                        role="button" tabindex="0"
                    >
                        <div class="flex items-center justify-end space-x-1">
                            <span>{{ __('Budget') }}</span>
                            @if ($sortField === 'budget')
                                <x-atoms.sort-direction :sortDirection="$sortDirection" />
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4 w-28 text-center">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>

            <tbody class="bg-custom-accent divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($categories as $index => $category)
                    <tr wire:key="cat-{{ $category->id }}" class="hover:bg-custom-accent transition-colors">
                        <td class="px-4 py-1 flex justify-center">
                            <input type="color" class="w-8 h-8 m-2 rounded cursor-pointer outline-none"
                                wire:change="updateCategoryColor($event.target.value, '{{ $category->id }}')"
                                value="{{ $category->color }}"
                                aria-label="{{ __('Category color for ') }} {{ $category->name }}" />

                        </td>
                        <td class="px-4 py-3">
                            <input type="text"
                                class="w-full px-3 py-2 border-transparent focus:border-zinc-300 focus:ring-1 focus:ring-custom rounded-md bg-custom-accent outline-none transition-all duration-150"
                                value="{{ $category->name }}"
                                wire:change="updateCategoryName($event.target.value, '{{ $category->id }}')"
                                aria-label="{{ __('Category name') }}" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="relative">
                                <input type="number"
                                    class="w-full px-3 py-2 text-right border-transparent focus:border-zinc-300 focus:ring-1 focus:ring-custom rounded-md bg-custom-accent outline-none transition-all duration-150"
                                    value="{{ $category->budget === null ? '' : number_format($category->budget, 2, '.', '') }}"
                                    wire:change="updateCategoryBudget($event.target.value, '{{ $category->id }}')"
                                    aria-label="{{ __('Category budget') }}" />
                            </div>
                        </td>
                        <td class="px-2">
                            <div class="flex items-center justify-center space-x-2">
                                <livewire:money.category-form :category="$category"
                                    wire:key="category-form-{{ $category->id }}" :edition="true" />
                                @if (!$category->wallet)
                                    <button type="button" wire:click="deleteCategory('{{ $category->id }}')"
                                        class="p-2 hover:text-danger-500 rounded-full hover:bg-danger-50 transition-colors duration-150 cursor-pointer"
                                        aria-label="{{ __('Delete this category') }}"
                                        title="{{ __('Delete this category') }}">
                                        <flux:icon.trash class="w-5 h-5" variant="micro" aria-hidden="true" />
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Mobile list -->
    <div class="block md:hidden">
        <div class="space-y-3 max-h-[60vh] overflow-y-auto">
            @foreach ($categories as $index => $category)
                <div wire:key="cat-mobile-{{ $category->id }}" class="rounded-lg border border-grey-accent bg-custom-accent p-4">
                    <!-- First row: Color and Name -->
                    <div class="flex items-center gap-3 mb-3 overflow-hidden">
                        <input type="color" class="w-8 h-8 rounded cursor-pointer outline-none flex-shrink-0"
                            wire:change="updateCategoryColor($event.target.value, '{{ $category->id }}')"
                            value="{{ $category->color }}"
                            aria-label="{{ __('Category color for ') }} {{ $category->name }}" />

                        <input type="text"
                            class="border-transparent focus:border-zinc-300 focus:ring-1 focus:ring-custom rounded-md bg-custom-accent outline-none transition-all duration-150"
                            value="{{ $category->name }}"
                            wire:change="updateCategoryName($event.target.value, '{{ $category->id }}')"
                            aria-label="{{ __('Category name') }}" />
                    </div>

                    <!-- Second row: Budget and Actions -->
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label class="block text-xs text-grey-inverse mb-1">{{ __('Budget') }}</label>
                            <input type="number"
                                class="w-full px-3 py-2 text-right border-transparent focus:border-zinc-300 focus:ring-1 focus:ring-custom rounded-md bg-custom-accent outline-none transition-all duration-150"
                                value="{{ number_format($category->budget, 2, '.', '') }}"
                                wire:change="updateCategoryBudget($event.target.value, '{{ $category->id }}')"
                                aria-label="{{ __('Category budget') }}" />
                        </div>

                        <div class="flex items-end gap-2 pb-1">
                            <livewire:money.category-form :category="$category" wire:key="category-form-mobile-{{ $category->id }}" :edition="true" mobile />
                            @if (!$category->wallet)
                                <button type="button" wire:click="deleteCategory('{{ $category->id }}')"
                                    class="p-2 hover:text-danger-500 rounded-full hover:bg-danger-50 transition-colors duration-150 cursor-pointer"
                                    aria-label="{{ __('Delete this category') }}"
                                    title="{{ __('Delete this category') }}">
                                    <flux:icon.trash class="w-5 h-5" variant="micro" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div
        class="mt-6 p-6 rounded-lg border-2 border-dashed border-grey-accent bg-custom-accent bg-opacity-50 shadow-sm hover:shadow-md transition-shadow">
        <h4 class="font-medium mb-6 text-grey">{{ __('Add a new category') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
            <div class="col-span-12 sm:col-span-1">
                <div class="flex justify-center">
                    <input type="color" wire:model.defer="newColor"
                        class="w-10 h-10 rounded cursor-pointer border border-zinc-300" aria-label="{{ __('New category color') }}" />
                </div>
            </div>
            <div class="col-span-12 sm:col-span-5">
                <flux:input type="text" wire:model.defer="newName" placeholder="{{ __('Category name') }}"
                    class="w-full" aria-label="{{ __('New category name') }}" />
            </div>
            <div class="col-span-12 sm:col-span-4">
                <div class="relative">
                    <flux:input type="number" wire:model.defer="newBudget" placeholder="0.00" step="1"
                        class="w-full" aria-label="{{ __('New category budget') }}" />
                </div>
            </div>
            <div class="col-span-12 sm:col-span-2">
                <flux:button wire:click="addCategory" wire:keydown.enter="addCategory" variant="primary"
                    class="w-full shadow-sm hover:shadow-md transition-shadow">
                    <span class="flex items-center justify-center">
                        <flux:icon.plus class="w-4 h-4 mr-1" aria-hidden="true" />
                        {{ __('Add') }}
                    </span>
                </flux:button>
            </div>
        </div>
    </div>
</div>
