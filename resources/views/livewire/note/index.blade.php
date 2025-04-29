<div>
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-96">
        {{-- Bouton d’ouverture du modal pour créer une note --}}
        <div
            class="flex-shrink-0 w-1/4 bg-custom p-6 shadow-sm hover:shadow-md transition-shadow flex items-center justify-center">
            <flux:modal.trigger name="edit-note" class="w-full h-full flex items-center justify-center cursor-pointer">
                <span class="m-1">Ajouter une note</span>
                <flux:icon.plus class="text-2xl text-white" />
            </flux:modal.trigger>
        </div>

        {{-- Liste des notes existantes --}}
        @forelse($notes as $note)
            <div class="flex-shrink-0 w-1/4 bg-custom p-6 shadow-sm hover:shadow-md transition-shadow relative">
                <div wire:click="delete('{{ $note->id }}')"
                    class="cursor-pointer absolute top-4 right-4 hover-custom hover:text-red-600 rounded-lg">
                    <flux:icon.x-mark />
                </div>
                <h3 class="text-xl font-semibold">{{ $note->name }}</h3>
                <p class="mt-2 text-sm">{{ $note->content }}</p>
            </div>
        @empty
            <div class="flex-shrink-0 w-full text-center py-10">
                Vous n'avez aucune note pour le moment...
            </div>
        @endforelse
    </div>

    {{-- Modal de création de note --}}
    <flux:modal name="edit-note">
        <div class="space-y-6">
            <div>
                <flux.heading size="2xl">Créer une nouvelle note</flux.heading>
                <flux.text class="mt-2">
                    Donnez un titre et rédigez votre texte.
                </flux.text>
            </div>

            {{-- Nom & Contenu --}}
            <flux:input label="Titre" placeholder="Titre de la note" wire:model.lazy="newNote.name" />
            <flux:textarea label="Contenu" placeholder="Votre texte ici..." rows="6"
                wire:model.lazy="newNote.content" />

            <div class="flex justify-end mt-4">
                <flux:button wire:click="create" variant="primary">
                    Créer la note
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
