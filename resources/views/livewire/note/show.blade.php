<div class="flex-1 flex flex-col bg-custom-accent !border-none rounded-xl overflow-hidden">
    <!-- Header avec bouton de fermeture -->
    <div class="flex items-center justify-between p-4 border-b m-4">
        <div class="flex items-center gap-4">
            @if($note && $note->id)
                @php
                    $title = Str::limit($note->content, 60);
                    foreach (['=', '-', '#'] as $delim) {
                        if (Str::contains($title, $delim)) {
                            $title = Str::before($title, $delim);
                        }
                    }
                    $title = trim($title);
                @endphp
                <div>
                    <h1 class="text-xl font-bold text-custom">
                        {{ $title ?: __('Untitled note') }}
                    </h1>
                    <p class="text-sm text-grey">
                        {{ __('Last updated') }}: {{ $note->updated_at->format('d/m/Y à H:i') }}
                    </p>
                </div>
            @else
                <div>
                    <h1 class="text-xl font-bold text-custom">{{ __('New note') }}</h1>
                    <p class="text-sm text-grey">{{ __('Start writing your note below') }}</p>
                </div>
            @endif
        </div>

        <button
            wire:click="closeNote"
            class="p-2 text-zinc-400 hover:text-custom rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-200"
            aria-label="{{ __('Close') }}">
            <flux:icon.x-mark class="w-6 h-6" aria-hidden="true" />
        </button>
    </div>

    <!-- Zone d'édition -->
    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
        <!-- Zone de saisie -->
        <div class="flex-1 flex flex-col">
            <div class="p-4">
                <h2 class="text-sm font-medium text-custom">{{ __('Write') }}</h2>
            </div>
            <textarea
                wire:model.live.debounce.750ms="markdownContent"
                class="w-full h-full p-6 focus:outline-none resize-none bg-transparent text-custom placeholder-grey input-none text-lg leading-relaxed"
                placeholder="{{ __('Start writing your note...') }}"
                aria-label="{{ __('Note content') }}">
            </textarea>
        </div>

        <!-- Séparateur -->
        <div class="w-px lg:h-full h-px lg:w-px bg-zinc-200 dark:bg-zinc-700"></div>

        <!-- Aperçu -->
        <div class="flex-1 flex flex-col lg:h-full h-96">
            <div class="p-4 bg-custom-ultra !border-none">
                <h2 class="text-sm font-medium text-custom">{{ __('Preview') }}</h2>
            </div>
            <div class="flex-1 p-6 overflow-y-auto">
                @if ($markdownContent)
                    <div class="prose prose-lg max-w-none text-custom">
                        @markdom($markdownContent)
                    </div>
                @else
                    <div class="flex items-center justify-center h-full text-grey">
                        <div class="text-center">
                            <flux:icon.eye class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" aria-hidden="true" />
                            <p class="text-lg">{{ __('Your note is empty.') }}</p>
                            <p class="text-sm mt-2">{{ __('Start typing to see the preview.') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer avec actions -->
    <div class="p-4">
        <div class="flex items-center justify-between border-t p-6">
            <div class="text-sm text-grey">
                @if($note && $note->id)
                    {{ __('Auto-saved') }} • {{ $note->updated_at->diffForHumans() }}
                @else
                    {{ __('Auto-save enabled') }}
                @endif
            </div>
            <div class="flex items-center gap-2">
                <flux:button
                    wire:click="closeNote"
                    class="px-4 py-2 text-sm text-grey hover:text-custom transition-colors duration-200">
                    {{ __('Close') }}
                </flux:button>
                @if($note && $note->id)
                    <flux:button
                        wire:click="deleteNote('{{ $note->id }}')"
                        class="px-4 py-2 text-sm text-danger-500 hover:text-danger-600 hover:bg-danger-100 rounded-lg transition-all duration-200">
                        {{ __('Delete') }}
                    </flux:button>
                @endif
            </div>
        </div>
    </div>
</div>
