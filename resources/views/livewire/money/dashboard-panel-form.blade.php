<div>
    <flux:modal.trigger name="panel-form-{{ $panel->id ?? 'create' }}" id="panel-form-{{ $panel->id ?? 'create' }}"
        class="w-full h-full flex items-center justify-center cursor-pointer"
        role="button"
        tabindex="0"
        aria-label="{{ $edition ? __('Edit panel') : __('Create panel') }}"
    >
        @if ($edition)
            <span class="flex items-center justify-center space-x-2">
                <flux:icon.adjustments-horizontal class="cursor-pointer ml-2" variant="micro" />
            </span>
        @else
            <span class="flex items-center justify-center space-x-2 rounded-lg">
                <span>{{ __('Create') }}</span>
                <flux:icon.plus variant="micro" />
            </span>
        @endif
    </flux:modal.trigger>

    <flux:modal name="panel-form-{{ $panel->id ?? 'create' }}" class="w-5/6" wire:cancel="resetForm">
        <div class="flex flex-col justify-between h-full">
            <div>
                @if ($edition)
                    <flux:heading size="2xl">{{ __('Edit your panel') }} « {{ $panel->title }} »</flux:heading>
                @else
                    <flux:heading size="2xl">{{ __('Create your panel') }}</flux:heading>
                @endif
            </div>

            <div class="mt-8">
                <div class="mb-6 p-4 bg-custom-accent">
                    <h3 class="text-lg font-medium text-custom mb-2">{{ __('Chart settings') }}</h3>

                    <!-- Titre du graphique -->
                    <div class="mb-4">
                        <flux:input wire:model="title" :label="__('Graphic title')" type="text" required autofocus
                            autocomplete="name" :placeholder="__('Annual expenses')" />
                    </div>

                    <!-- Type de graphique -->
                    <div class="mb-4">
                        <label for="type" class="block text-sm font-medium text-grey-inverse mb-1">{{ __('Chart type') }}</label>
                        <flux:select id="type" wire:model="type">
                            <option value="" disabled selected>{{ __('Select a chart type') }}</option>
                            <option value="bar">{{ __('Bars') }}</option>
                            <option value="doughnut">{{ __('Ring') }}</option>
                            <option value="pie">{{ __('Pie') }}</option>
                            <option value="line">{{ __('Linear') }}</option>
                            <option value="table">{{ __('Table') }}</option>
                            <option value="number">{{ __('Number') }}</option>
                        </flux:select>
                    </div>
                </div>

                <!-- Données à analyser -->
                <div class="mb-6 p-4 bg-custom-accent">
                    <h3 class="text-lg font-medium text-custom mb-4">{{ __('Data to analyze') }}</h3>

                    <!-- Comptes bancaires -->
                    <div class="mb-4">
                        <x-atoms.select
                            name="accountsId[]"
                            wire:model="accountsId"
                            :label="__('Bank accounts')"
                            :options="$bankAccounts"
                            :selected="$accountsId ?? []"
                            :placeholder="__('Search...')"
                            :showChips="true"
                        />
                    </div>

                    <!-- Catégories -->
                    <div class="mb-4">
                        <x-atoms.select
                            name="categoriesId[]"
                            wire:model="categoriesId"
                            :label="__('Categories')"
                            :options="$categories"
                            :selected="$categoriesId ?? []"
                            :placeholder="__('Search...')"
                            :showChips="true"
                        />
                    </div>
                </div>

                <!-- Période d'analyse -->
                <div class="mb-6 p-4 bg-custom-accent">
                    <h3 class="text-lg font-medium text-custom mb-2">{{ __('Analysis period') }}</h3>

                    <!-- Type de période -->
                    <div class="mb-4">
                        <label for="periodType"
                            class="block text-sm font-medium text-grey-inverse mb-1">{{ __('Period type') }}</label>
                        <flux:select id="periodType" wire:model="periodType">
                            <option value="" disabled selected>{{ __('Select a period type') }}</option>
                            <option value="all">{{ __('All periods') }}</option>
                            <option value="daily">{{ __('Daily (1 day)') }}</option>
                            <option value="weekly">{{ __('Weekly (7 days)') }}</option>
                            <option value="biweekly">{{ __('Bi-weekly (14 days)') }}</option>
                            <option value="monthly">{{ __('Monthly (30 days)') }}</option>
                            <option value="quarterly">{{ __('Quarterly (90 days)') }}</option>
                            <option value="biannual">{{ __('Bi-annual (180 days)') }}</option>
                            <option value="yearly">{{ __('Yearly (365 days)') }}</option>
                            <option value="actual_month">{{ __('Actual month') }}</option>
                            <option value="previous_month">{{ __('Last month') }}</option>
                            <option value="two_months_ago">{{ __('Two months ago') }}</option>
                            <option value="three_months_ago">{{ __('Three months ago') }}</option>
                        </flux:select>
                    </div>
                </div>
            </div>
        </div>


        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost" class="px-4">
                    {{ __('Cancel') }}
                </flux:button>
            </flux:modal.close>
            <flux:button wire:click="save" variant="primary" wire:keydown.enter="save">
                @if ($edition)
                    {{ __('Update') }}
                @else
                    {{ __('Create') }}
                @endif
            </flux:button>
        </div>
    </flux:modal>
</div>
