<div>
    @if ($edition)
        <flux:modal.trigger name="panel-form-{{ $panel->id }}" id="panel-form-{{ $panel->id }}"
            class="w-full h-full flex items-center justify-center cursor-pointer group"
            role="button"
            tabindex="0"
            aria-label="{{ __('Edit panel') }}"
        >
            <span class="flex items-center justify-center space-x-2 p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                <flux:icon.adjustments-horizontal class="cursor-pointer text-zinc-500 dark:text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300" variant="micro" />
            </span>
        </flux:modal.trigger>
    @else
        <flux:modal.trigger name="panel-form-create" id="panel-form-create"
            class="h-full group bg-white/50 dark:bg-zinc-800/50 backdrop-blur-sm rounded-2xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 hover:border-zinc-400 dark:hover:border-zinc-500 transition-all duration-300 cursor-pointer min-h-[300px] flex items-center justify-center"
            role="button"
            tabindex="0"
            aria-label="{{ __('Add new panel') }}"
        >
            <div class="text-center p-8">
                <div class="w-16 h-16 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700 rounded-xl flex items-center justify-center mb-4 mx-auto group-hover:scale-110 transition-transform duration-200">
                    <flux:icon.plus class="w-8 h-8 text-zinc-500 dark:text-zinc-400" />
                </div>
                <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Add New Panel') }}</h3>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">{{ __('Create a beautiful visualization') }}</p>
            </div>
        </flux:modal.trigger>
    @endif

    <flux:modal name="panel-form-{{ $panel->id ?? 'create' }}" class="w-2/3 h-full" wire:cancel="resetForm">
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="text-center pb-4 border-b border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                @if ($edition)
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Edit Panel') }}</h2>
                @else
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Create New Panel') }}</h2>
                @endif
            </div>

            <!-- Form Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="space-y-8">
                <!-- Basic Settings -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Panel Title -->
                    <flux:field>
                        <flux:label>{{ __('Panel Title') }}</flux:label>
                        <flux:input wire:model="title" placeholder="{{ __('Enter a descriptive title') }}" />
                        <flux:error name="title" />
                    </flux:field>

                    <!-- Chart Type -->
                    <flux:field>
                        <flux:label>{{ __('Chart Type') }}</flux:label>
                        <flux:select wire:model="type" placeholder="{{ __('Select a chart type') }}">
                            <option value="bar">{{ __('Bars') }}</option>
                            <option value="doughnut">{{ __('Ring') }}</option>
                            <option value="pie">{{ __('Pie') }}</option>
                            <option value="line">{{ __('Linear') }}</option>
                            <option value="table">{{ __('Table') }}</option>
                            <option value="number">{{ __('Total Amount') }}</option>
                            <option value="gauge">{{ __('Income vs Expenses') }}</option>
                            <option value="trend">{{ __('Daily Trend') }}</option>
                            <option value="category_comparison">{{ __('Category Comparison') }}</option>
                        </flux:select>
                        <flux:error name="type" />
                    </flux:field>
                </div>

                <!-- Time Period -->
                <flux:field>
                    <flux:label>{{ __('Time Period') }}</flux:label>
                    <flux:select wire:model="periodType" placeholder="{{ __('Select a time period') }}">
                        <option value="daily">{{ __('Daily') }}</option>
                        <option value="weekly">{{ __('Weekly') }}</option>
                        <option value="biweekly">{{ __('Biweekly') }}</option>
                        <option value="monthly">{{ __('Monthly') }}</option>
                        <option value="quarterly">{{ __('Quarterly') }}</option>
                        <option value="biannual">{{ __('Biannual') }}</option>
                        <option value="yearly">{{ __('Yearly') }}</option>
                        <option value="actual_month">{{ __('Current Month') }}</option>
                        <option value="previous_month">{{ __('Previous Month') }}</option>
                        <option value="two_months_ago">{{ __('Two Months Ago') }}</option>
                        <option value="three_months_ago">{{ __('Three Months Ago') }}</option>
                        <option value="all">{{ __('All Period') }}</option>
                    </flux:select>
                    <flux:error name="periodType" />
                </flux:field>

                <!-- Data Sources -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-50 flex items-center">
                        <flux:icon.banknotes class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" />
                        {{ __('Data Sources') }}
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Bank Accounts -->
                        <flux:field>
                            <flux:label>{{ __('Bank Accounts') }}</flux:label>
                            <div x-data="{
                                open: false,
                                search: '',
                                selectedAccounts: @entangle('accountsId'),
                                accounts: @js($bankAccounts),
                                get filteredAccounts() {
                                    return this.accounts.filter(account =>
                                        account.name.toLowerCase().includes(this.search.toLowerCase())
                                    );
                                },
                                toggleAccount(accountId) {
                                    const index = this.selectedAccounts.indexOf(accountId);
                                    if (index > -1) {
                                        this.selectedAccounts.splice(index, 1);
                                    } else {
                                        this.selectedAccounts.push(accountId);
                                    }
                                },
                                isSelected(accountId) {
                                    return this.selectedAccounts.includes(accountId);
                                }
                            }" class="relative">
                                <button @click="open = !open" type="button"
                                        class="w-full px-4 py-3 text-left bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <span x-show="selectedAccounts.length === 0" class="text-zinc-500 dark:text-zinc-400">
                                        {{ __('Select accounts') }}
                                    </span>
                                    <span x-show="selectedAccounts.length > 0" class="text-zinc-900 dark:text-zinc-50">
                                        <span x-text="selectedAccounts.length"></span> {{ __('account(s) selected') }}
                                    </span>
                                    <flux:icon.chevron-down class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-zinc-400" />
                                </button>

                                <div x-show="open" @click.away="open = false"
                                     class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-lg max-h-60 overflow-hidden">
                                    <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
                                        <flux:input x-model="search" placeholder="{{ __('Search accounts...') }}"
                                                   class="w-full text-sm" />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <template x-for="account in filteredAccounts" :key="account.id">
                                            <div @click="toggleAccount(account.id)"
                                                 class="px-4 py-3 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer flex items-center justify-between">
                                                <span class="text-sm text-zinc-900 dark:text-zinc-50" x-text="account.name"></span>
                                                <flux:icon.check x-show="isSelected(account.id)" class="w-4 h-4 text-blue-600" />
                                            </div>
                                        </template>
                                        <div x-show="filteredAccounts.length === 0" class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ __('No accounts found') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <flux:error name="accountsId" />
                        </flux:field>

                        <!-- Categories -->
                        <flux:field>
                            <flux:label>{{ __('Categories') }}</flux:label>
                            <div x-data="{
                                open: false,
                                search: '',
                                selectedCategories: @entangle('categoriesId'),
                                categories: @js($categories),
                                get filteredCategories() {
                                    return this.categories.filter(category =>
                                        category.name.toLowerCase().includes(this.search.toLowerCase())
                                    );
                                },
                                toggleCategory(categoryId) {
                                    const index = this.selectedCategories.indexOf(categoryId);
                                    if (index > -1) {
                                        this.selectedCategories.splice(index, 1);
                                    } else {
                                        this.selectedCategories.push(categoryId);
                                    }
                                },
                                isSelected(categoryId) {
                                    return this.selectedCategories.includes(categoryId);
                                }
                            }" class="relative">
                                <button @click="open = !open" type="button"
                                        class="w-full px-4 py-3 text-left bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <span x-show="selectedCategories.length === 0" class="text-zinc-500 dark:text-zinc-400">
                                        {{ __('Select categories') }}
                                    </span>
                                    <span x-show="selectedCategories.length > 0" class="text-zinc-900 dark:text-zinc-50">
                                        <span x-text="selectedCategories.length"></span> {{ __('category(ies) selected') }}
                                    </span>
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
                                            <div @click="toggleCategory(category.id)"
                                                 class="px-4 py-3 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer flex items-center justify-between">
                                                <span class="text-sm text-zinc-900 dark:text-zinc-50" x-text="category.name"></span>
                                                <flux:icon.check x-show="isSelected(category.id)" class="w-4 h-4 text-blue-600" />
                                            </div>
                                        </template>
                                        <div x-show="filteredCategories.length === 0" class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ __('No categories found') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <flux:error name="categoriesId" />
                        </flux:field>
                    </div>

                    {{-- <!-- Display Options -->
                    <flux:field>
                        <flux:checkbox wire:model="displayUncategorized" />
                        <flux:label>{{ __('Include uncategorized transactions') }}</flux:label>
                    </flux:field> --}}
                </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-end space-x-3 p-6 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 flex-shrink-0">
                <flux:button wire:click="resetForm" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="save" variant="primary">
                    @if ($edition)
                        {{ __('Update Panel') }}
                    @else
                        {{ __('Create Panel') }}
                    @endif
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
