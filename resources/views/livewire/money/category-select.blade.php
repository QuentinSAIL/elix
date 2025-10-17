<div id="div-category-form-{{ $modalId }}">
    <flux:modal.trigger name="category-form-{{ $modalId }}" id="category-form-{{ $modalId }}"
        class="w-full h-full flex items-center justify-center cursor-pointer px-2"
        role="button"
        tabindex="0"
        aria-label="{{ __('Edit category for transaction') }}"
    >
        <div class="text-center w-full">
            @if ($category)
                <span class="cursor-pointer whitespace-normal break-words">@limit($category->name, 24)</span>
            @else
                <span class="cursor-pointer">-</span>
            @endif
        </div>
    </flux:modal.trigger>

    <flux:modal name="category-form-{{ $modalId }}" class="w-5/6 h-3/5">
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex-shrink-0 p-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="2xl">{{ __('Add a new category') }}</flux:heading>
                @if ($transaction)
                    <flux:text class="mt-2">
                        @if ($category)
                            {{ __('Edit the category of your transaction') }}
                        @else
                            {{ __('Add a category to your transaction') }}
                        @endif
                        <span class="font-extrabold block whitespace-normal break-words"> « {{ $transaction->description }} »</span>
                    </flux:text>
                @endif
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <form class="w-full text-left">
                    <label for="category" class="font-bold">{{ __('Category') }}</label>

                    <div class="flex flex-col space-y-4">
                        <!-- Searchable Category Select -->
                        <div x-data="{
                            open: false,
                            search: '',
                            selectedCategory: @entangle('selectedCategory'),
                            categories: @js($categories),
                            get filteredCategories() {
                                return this.categories.filter(category =>
                                    category.name.toLowerCase().includes(this.search.toLowerCase())
                                );
                            },
                            selectCategory(categoryName) {
                                this.selectedCategory = categoryName;
                                this.open = false;
                                this.search = '';
                            },
                            clearSelection() {
                                this.selectedCategory = '';
                                this.open = false;
                                this.search = '';
                            }
                        }" class="relative">
                            <button @click="open = !open" type="button"
                                    class="w-full px-4 py-3 text-left bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus-ring-color>
                                <span x-show="!selectedCategory" class="text-zinc-500 dark:text-zinc-400">
                                    {{ __('Select a category') }}
                                </span>
                                <span x-show="selectedCategory" class="text-zinc-900 dark:text-zinc-50" x-text="selectedCategory"></span>
                                <flux:icon.chevron-down class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-zinc-400" />
                            </button>

                            <div x-show="open" @click.away="open = false"
                                 class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-lg max-h-60 overflow-hidden">
                                <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
                                    <flux:input x-model="search" placeholder="{{ __('Search categories...') }}"
                                               class="w-full text-sm" />
                                </div>
                                <div class="max-h-48 overflow-y-auto">
                                    <template x-for="category in filteredCategories" :key="category.id">
                                        <div @click="selectCategory(category.name)"
                                             class="px-4 py-3 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer flex items-center justify-between">
                                            <span class="text-sm text-zinc-900 dark:text-zinc-50" x-text="category.name"></span>
                                            <flux:icon.check x-show="selectedCategory === category.name" class="w-4 h-4 text-color" />
                                        </div>
                                    </template>
                                    <div x-show="filteredCategories.length === 0" class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ __('No categories found') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if (!$alreadyExists && $selectedCategory)
                            <flux:text class="mt-4 mb-3 text-gray-400" role="status">
                                {{ __('The category :name does not exist yet.', ['name' => $selectedCategory]) }}
                            </flux:text>

                            <div class="space-y-6">
                                <flux:textarea :label="__('Description (optional)')" wire:model.lazy="description" />
                            </div>
                        @endif

                        <flux:switch :label="__('Add other transactions to this category')"
                            wire:model.lazy="addOtherTransactions" />

                        @if ($addOtherTransactions)
                            <flux:input :label="__('Keyword to match')" placeholder="{{ __('Example: Amazon payment') }}"
                                wire:model.lazy="keyword" />
                        @endif
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="flex-shrink-0">
                <div class="flex justify-end">
                    <flux:button wire:click="save" variant="primary" wire:keydown.enter="save">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
