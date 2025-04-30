<div class="h-full p-8">
    <div x-data="{ markdownContent: @js($markdownContent ?? '') }" class="flex flex-row h-full overflow-y-scroll">
        <div class="py-3 flex-1 flex flex-col">
            <h3 class="text-lg">Entrée</h3>
            <textarea x-model="markdownContent" class="w-full flex-1 p-4 mt-2 focus:outline-none resize-none"
                placeholder="Contenu de la note"></textarea>
        </div>

        <div class="p-3 flex-1 flex flex-col">
            <h3 class="text-lg">Sortie</h3>
            <div class="w-full flex-1 p-2 mt-2 overflow-y-auto">
                <div x-init="$watch('markdownContent', value => $wire.set('markdownContent', value))">
                    @markdom($markdownContent ?? '### Markdown ici 😎')
                </div>
            </div>
        </div>
    </div>
    <div class="text-right -mt-8">
        <flux:button variant="primary" wire:click="save" class="bg-custom-accent">
            Enregistrer
        </flux:button>
    </div>
</div>
