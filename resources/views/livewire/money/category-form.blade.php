<div>
    <!-- Déclencheur du modal avec styles améliorés -->
    <flux:modal.trigger name="category-form-{{ $categoryId }}" id="category-form-{{ $categoryId }}"
        class="w-full h-full flex items-center justify-center cursor-pointer">
        @if ($edition)
            <div class="group p-2 rounded-lg">
                <flux:icon.pencil-square class="cursor-pointer" variant="micro" />
            </div>
        @else
            <span class="flex items-center justify-center space-x-2 py-2 px-4">
                <span>{{ __('Créer une catégorie') }}</span>
                <flux:icon.plus variant="micro" />
            </span>
        @endif
    </flux:modal.trigger>

    <!-- Modal avec animation et structure améliorée -->
    <flux:modal name="category-form-{{ $categoryId }}" class="w-5/6 max-w-2xl" wire:cancel="resetForm">
        <div class="space-y-6">
            <div>
                @if ($edition)
                    <flux:heading size="2xl" class="flex items-center space-x-2">
                        <span>{{ __('Modifier la catégorie') }}</span>
                        <span class="font-bold">« {{ $category->name }} »</span>
                    </flux:heading>
                @else
                    <flux:heading size="2xl">{{ __('Créer une nouvelle catégorie') }}</flux:heading>
                @endif
            </div>

            <!-- Section d'informations principales avec regroupement visuel -->
            <div class="space-y-4 p-4 bg-custom-accent rounded-lg">
                <flux:heading size="lg" class="mb-2">{{ __('Informations générales') }}</flux:heading>

                <flux:input :label="__('Nom de la catégorie')" :placeholder="__('Ex: Loyer')"
                    wire:model.lazy="categoryForm.name" />

                <flux:textarea :label="__('Description (optionnelle)')"
                    :placeholder="__('Décrivez à quoi sert cette catégorie...')"
                    wire:model.lazy="categoryForm.description" />

                <div class="flex items-center pt-2">
                    <flux:switch :label="__('Inclure dans les statistiques')"
                        wire:model.lazy="categoryForm.include_in_dashboard" />
                    <div class="ml-2 text-sm text-zinc-500">
                        <flux:icon.information-circle class="inline-block w-4 h-4" />
                        {{ __('Les catégories actives apparaissent dans les graphiques et analyses') }}
                    </div>
                </div>
            </div>

            <div class="space-y-4 p-4 bg-custom-accent rounded-lg">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Correspondance des transactions') }}</flux:heading>
                    <button wire:click="addCategoryMatch"
                        class="flex items-center space-x-1 text-sm bg-custom px-3 py-1 rounded-md hover:bg-custom-accent">
                        <span>{{ __('Ajouter un mot-clé') }}</span>
                        <flux:icon.plus class="w-4 h-4" />
                    </button>
                </div>

                <p class="text-sm text-zinc-500">
                    {{ __('Ajoutez des mots-clés pour que les transactions correspondantes soient automatiquement associées à cette catégorie.') }}
                </p>

                <!-- Liste des correspondances avec design amélioré -->
                <div class="max-h-64 overflow-y-auto pr-2">
                    @if ($categoryMatchForm && count($categoryMatchForm) > 0)
                        <div class="space-y-3">
                            @foreach ($categoryMatchForm as $index => $match)
                                <div class="flex items-center space-x-2 p-4 bg-custom rounded-lg">
                                    <flux:input :placeholder="__('Ex: Carrefour, Netflix, EDF')"
                                        wire:model.lazy="categoryMatchForm.{{ $index }}.keyword"
                                        class="flex-1 !focus:outline-none" />
                                    <button wire:click="removeCategoryMatch({{ $index }})" class="p-1">
                                        <flux:icon.trash class="cursor-pointer text-red-500 w-5 h-5"
                                            title="{{ __('Supprimer ce mot-clé') }}" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 text-zinc-500">
                            {{ __('Aucun mot-clé ajouté. Cliquez sur "Ajouter un mot-clé" pour commencer.') }}
                        </div>
                    @endif
                </div>

                @if ($this->getHasMatchChangesProperty())
                    <div class="mt-4 p-4 bg-custom rounded-lg">
                        <div class="flex items-start space-x-3 mb-3">
                            <flux:icon.information-circle class="w-5 h-5 mt-0.5 flex-shrink-0" />
                            <div class="text-sm">
                                <p class="font-medium mb-1">
                                    {{ __('Modifications détectées dans les correspondances') }}</p>
                                <p class="">
                                    {{ __('Ces modifications peuvent être appliquées aux transactions existantes. Sélectionnez les options ci-dessous:') }}
                                </p>
                            </div>
                        </div>

                        <div class="pl-8 space-y-3">
                            <div class="flex items-center justify-between p-2 bg-custom-accent rounded-md">
                                <div>
                                    <flux:switch wire:model.lazy="applyMatch" id="apply-match" />
                                    <label for="apply-match" class="ml-2 text-sm font-medium cursor-pointer">
                                        {{ __('Appliquer aux transactions existantes') }}
                                    </label>
                                </div>
                                <flux:icon.arrow-path class="w-4 h-4" />
                            </div>

                            @if ($applyMatch)
                                <div class="flex items-center justify-between p-2 bg-custom-accent rounded-md">
                                    <div>
                                        <flux:switch wire:model.lazy="applyMatchToAlreadyCategorized"
                                            id="apply-match-categorized" />
                                        <label for="apply-match-categorized"
                                            class="ml-2 text-sm font-medium cursor-pointer">
                                            {{ __('Écraser les catégories existantes') }}
                                        </label>
                                    </div>
                                    <flux:icon.document-duplicate class="w-4 h-4 " />
                                </div>
                            @endif

                            <p class="text-xs italic">
                                {{ __('Note: Les transactions existantes seront analysées et catégorisées selon ces nouveaux mots-clés lors de la sauvegarde.') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex gap-x-3 mt-6 justify-end pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost" class="px-4">
                        {{ __('Annuler') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button wire:click="save" variant="primary" wire:keydown.enter="save">
                    @if ($edition)
                        {{ __('Mettre à jour') }}
                    @else
                        {{ __('Créer') }}
                    @endif
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
