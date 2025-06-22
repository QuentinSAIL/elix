<div>
    <flux:modal.trigger name="panel-form-{{ $panel->id ?? 'create' }}" id="panel-form-{{ $panel->id ?? 'create' }}"
        class="w-full h-full flex items-center justify-center cursor-pointer">
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
                    <h3 class="text-lg font-medium text-custom mb-2">Paramètres du graphique</h3>

                    <!-- Titre du graphique -->
                    <div class="mb-4">
                        <flux:input wire:model="title" :label="__('Graphic title')" type="text" required autofocus
                            autocomplete="name" :placeholder="__('Annual expenses')" />
                    </div>

                    <!-- Type de graphique -->
                    <div class="mb-4">
                        <label for="type" class="block text-sm font-medium text-grey-inverse mb-1">Type de
                            graphique</label>
                        <flux:select id="type" wire:model="type">
                            <option value="" disabled selected>Sélectionnez un type de graphique</option>
                            <option value="bar">Barres</option>
                            <option value="doughnut">Anneau</option>
                            <option value="pie">Camembert</option>
                            <option value="line">Linéaire</option>
                            <option value="table">Tableau</option>
                            <option value="number">Nombre</option>
                        </flux:select>
                    </div>
                </div>

                <!-- Données à analyser -->
                <div class="mb-6 p-4 bg-custom-accent">
                    <h3 class="text-lg font-medium text-custom mb-4">Données à analyser</h3>

                    <!-- Comptes bancaires -->
                    <div class="mb-4">
                        <x-atoms.select
                            name="accountsId[]"
                            wire:model="accountsId"
                            label="Comptes bancaires"
                            :options="$bankAccounts"
                            :selected="$accountsId ?? []"
                            placeholder="Rechercher..."
                            :showChips="true"
                        />
                    </div>

                    <!-- Catégories -->
                    <div class="mb-4">
                        <x-atoms.select
                            name="categoriesId[]"
                            wire:model="categoriesId"
                            label="Catégories"
                            :options="$categories"
                            :selected="$categoriesId ?? []"
                            placeholder="Rechercher..."
                            :showChips="true"
                        />
                    </div>
                </div>

                <!-- Période d'analyse -->
                <div class="mb-6 p-4 bg-custom-accent">
                    <h3 class="text-lg font-medium text-custom mb-2">Période d'analyse</h3>

                    <!-- Type de période -->
                    <div class="mb-4">
                        <label for="periodType"
                            class="block text-sm font-medium text-grey-inverse mb-1">Typedepériode</label>
                        <flux:select id="periodType" wire:model="periodType">
                            <option value="" disabled selected>Sélectionnez un type de période</option>
                            <option value="all">Toutes les périodes</option>
                            <option value="daily">Journalière (1 jour)</option>
                            <option value="weekly">Hebdomadaire (7 jours)</option>
                            <option value="biweekly">Bi-hebdomadaire (14 jours)</option>
                            <option value="monthly">Mensuelle (30 jours)</option>
                            <option value="quarterly">Trimestrielle (90 jours)</option>
                            <option value="biannual">Semestrielle (180 jours)</option>
                            <option value="yearly">Annuelle (365 jours)</option>
                        </flux:select>
                    </div>
                </div>
            </div>
        </div>


        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost" class="px-4">
                    {{ __('Annuler') }}
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
