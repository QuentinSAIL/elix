<div class="min-h-screen bg-custom-accent">
    <!-- Header avec bouton d'ajout -->
    <div class="sticky top-0 z-10 bg-custom-accent border-b border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-custom">{{ __('Notes') }}</h1>
            <button
                wire:click="selectNote('{{ null }}')"
                class="bg-color text-white rounded-xl px-6 py-3 flex items-center gap-2 hover:opacity-90 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
                aria-label="{{ __('Add new note') }}">
                <flux:icon.plus class="w-5 h-5" aria-hidden="true" />
                <span class="font-medium">{{ __('Add new note') }}</span>
            </button>
        </div>
    </div>

    <!-- Grille de cartes -->
    <div class="p-6">
        @if($notes->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
                @foreach($notes as $note)
                    <div class="group cursor-pointer transform transition-all duration-300 hover:scale-105 hover:shadow-xl"
                        wire:click="selectNote('{{ $note->id }}')" role="button" tabindex="0">
                        <div class="bg-custom-ultra rounded-2xl p-6 h-48 flex flex-col justify-between border border-zinc-200 dark:border-zinc-700 hover:border-primary-300 dark:hover:border-primarydark-300 transition-all duration-200 {{ $selectedNote?->id === $note->id ? 'ring-2 ring-primary-500 dark:ring-primarydark-500 shadow-lg' : '' }}">
                            <!-- Contenu de la note -->
                            <div class="flex-1">
                                @php
                                    $excerpt = Str::limit($note->content, 120);
                                    foreach (['=', '-', '#'] as $delim) {
                                        if (Str::contains($excerpt, $delim)) {
                                            $excerpt = Str::before($excerpt, $delim);
                                        }
                                    }
                                    $excerpt = trim($excerpt);
                                @endphp
                                <h3 class="font-semibold text-lg text-custom mb-2 line-clamp-2">
                                    {{ Str::limit(strip_tags($excerpt), 16) ?: __('Untitled note') }}
                                </h3>
                                <p class="text-sm text-grey line-clamp-4">
                                    {{ Str::limit(strip_tags($note->content), 150) }}
                                </p>
                            </div>

                            <!-- Footer avec date et actions -->
                            <div class="flex items-center justify-between mt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <p class="text-xs text-grey">
                                    {{ $note->updated_at->diffForHumans() }}
                                </p>
                                <button
                                    type="button"
                                    wire:click.stop="delete('{{ $note->id }}')"
                                    class="opacity-0 group-hover:opacity-100 p-1 text-zinc-400 hover:text-danger-500 rounded-full transition-all duration-200 hover:bg-danger-100"
                                    aria-label="{{ __('Delete this note') }}"
                                    title="{{ __('Delete this note') }}">
                                    <flux:icon.trash class="w-4 h-4" variant="micro" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- État vide -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6">
                    <flux:icon.document-text class="w-12 h-12 text-zinc-400 dark:text-zinc-600" aria-hidden="true" />
                </div>
                <h2 class="text-xl font-semibold text-custom mb-2">{{ __('No notes yet') }}</h2>
                <p class="text-grey mb-6 max-w-md">{{ __('Start creating your first note to organize your thoughts and ideas.') }}</p>
                <button
                    wire:click="selectNote('{{ null }}')"
                    class="bg-color text-white rounded-xl px-6 py-3 flex items-center gap-2 hover:opacity-90 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <flux:icon.plus class="w-5 h-5" aria-hidden="true" />
                    <span class="font-medium">{{ __('Create your first note') }}</span>
                </button>
            </div>
        @endif
    </div>

    <!-- Overlay plein écran pour l'édition -->
    @if($selectedNote !== null)
        <div class="fixed inset-0 z-50 backdrop-blur-md bg-opacity-30 flex items-center justify-center p-4"
             wire:click="closeModal"
             x-data="{}"
             x-on:keydown.escape.window="$wire.closeModal()">
            <div class="bg-custom-accent rounded-lg w-full max-w-6xl h-[90vh] flex flex-col shadow-2xl transform transition-all duration-300"
                 wire:click.stop>
                <livewire:note.show :note="$selectedNote" wire:key="note-{{ $selectedNote?->id }}" />
            </div>
        </div>
    @endif
</div>
