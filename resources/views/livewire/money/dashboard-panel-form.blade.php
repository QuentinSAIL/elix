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
                    <div class="mb-4" x-data="{
                        open: false,
                        search: '',
                        selectedIds: @entangle('accountsId'),
                        items: @js($bankAccounts),
                        filtered() {
                            return Object.entries(this.items)
                                .filter(([id, name]) =>
                                    name.toLowerCase().includes(this.search.toLowerCase())
                                );
                        },
                        isSelected(id) {
                            return this.selectedIds.includes(id);
                        },
                        add(id) {
                            this.selectedIds.push(id);
                            this.search = '';
                        },
                        remove(id) {
                            this.selectedIds = this.selectedIds.filter(i => i !== id);
                        }
                    }" class="relative">
                        <label class="block text-sm font-medium text-grey-inverse mb-1">Comptes bancaires</label>

                        <!-- Trigger -->
                        <div @click="open = !open"
                            class="flex justify-between bg-custom items-center w-full border-grey px-4 py-2 cursor-pointer">
                            <div>
                                <template x-if="selectedIds.length === 0">
                                    <span class="text-grey">Rechercher...</span>
                                </template>
                                <template x-if="selectedIds.length > 0">
                                    <span x-text="selectedIds.map(id => items[id]).join(', ')"></span>
                                </template>
                            </div>
                            <x-atoms.sort-direction />
                        </div>

                        <!-- Dropdown -->
                        <div x-show="open" @click.away="open = false"
                            class="absolute z-10 w-max-3/4 mt-1 border-grey shadow-lg bg-custom">
                            <div class="p-2">
                                <input x-model="search" type="text" placeholder="Rechercher..."
                                    class="w-full border-grey px-3 py-2 placeholder-text-grey focus:ring-2 focus:ring-color">
                            </div>
                            <div class="max-h-60 overflow-y-auto">
                                <template x-for="[id, name] in filtered()" :key="id">
                                    <div @click.stop="isSelected(id) ? remove(id) : add(id)"
                                        class="px-3 py-2 cursor-pointer hover flex items-center justify-between">
                                        <span x-text="name" class=""></span>
                                        <svg x-show="isSelected(id)" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 text-color" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </template>
                                <div x-show="filtered().length === 0" class="px-3 py-2 text-grey">Aucun
                                    résultat</div>
                            </div>
                        </div>

                        <!-- Chips -->
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="id in selectedIds" :key="id">
                                <div class="rounded-sm selected px-2 py-1 flex items-center space-x-1">
                                    <span x-text="items[id]"></span>
                                    <button type="button" @click="remove(id)" class="ml-1 icon-danger-small">
                                        <flux:icon.x-mark variant="micro" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Catégories -->
                    <div class="mb-4" x-data="{
                        open: false,
                        search: '',
                        selectedIds: @entangle('categoriesId'),
                        items: @js($categories),
                        filtered() {
                            return Object.entries(this.items)
                                .filter(([id, name]) =>
                                    name.toLowerCase().includes(this.search.toLowerCase())
                                );
                        },
                        isSelected(id) {
                            return this.selectedIds.includes(id);
                        },
                        add(id) {
                            this.selectedIds.push(id);
                            this.search = '';
                        },
                        remove(id) {
                            this.selectedIds = this.selectedIds.filter(i => i !== id);
                        }
                    }" class="relative">
                        <label class="block text-sm font-medium text-grey-inverse mb-1">Catégories</label>

                        <!-- Trigger -->
                        <div @click="open = !open"
                            class="flex justify-between bg-custom items-center w-full border-grey px-4 py-2 cursor-pointer">
                            <div>
                                <template x-if="selectedIds.length === 0">
                                    <span class="text-grey">Rechercher...</span>
                                </template>
                                <template x-if="selectedIds.length > 0">
                                    <span x-text="selectedIds.map(id => items[id]).join(', ')"></span>
                                </template>
                            </div>
                            <x-atoms.sort-direction />
                        </div>

                        <!-- Dropdown -->
                        <div x-show="open" @click.away="open = false"
                            class="absolute z-10 w-max-3/4 mt-1 border-grey shadow-lg bg-custom">
                            <div class="p-2">
                                <input x-model="search" type="text" placeholder="Rechercher..."
                                    class="w-full border-grey px-3 py-2 placeholder-text-grey focus:ring-2 focus:ring-color">
                            </div>
                            <div class="max-h-60 overflow-y-auto">
                                <template x-for="[id, name] in filtered()" :key="id">
                                    <div @click.stop="isSelected(id) ? remove(id) : add(id)"
                                        class="px-3 py-2 cursor-pointer hover flex items-center justify-between">
                                        <span x-text="name" class=""></span>
                                        <svg x-show="isSelected(id)" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 text-color" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </template>
                                <div x-show="filtered().length === 0" class="px-3 py-2 text-grey">Aucun
                                    résultat</div>
                            </div>
                        </div>

                        <!-- Chips -->
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="id in selectedIds" :key="id">
                                <div class="rounded-sm selected px-2 py-1 flex items-center space-x-1">
                                    <span x-text="items[id]"></span>
                                    <button type="button" @click="remove(id)" class="ml-1 icon-danger-small">
                                        <flux:icon.x-mark variant="micro" />
                                    </button>
                                </div>
                            </template>
                        </div>
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
