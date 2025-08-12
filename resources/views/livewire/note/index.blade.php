<div class="">
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-48">
        <div class="flex-shrink-0 w-1/4 h-full bg-custom-accent p-6 shadow-sm hover:shadow-md transition-shadow flex items-center justify-center cursor-pointer {{ $selectedNote?->id === null ? 'border-color' : '' }}"
            wire:click="selectNote('{{ null }}')" aria-label="{{ __('Add new note') }}">
            <span class="m-1">
                {{ __('Add new note') }}
            </span>
            <flux:icon.plus class="text-2xl text-white" />
        </div>

        @forelse($notes as $note)
            <div class="flex-shrink-0 w-1/4 h-full bg-custom-accent p-6 shadow-sm hover:shadow-md transition-shadow relative cursor-pointer {{ $selectedNote?->id === $note->id ? 'border-color' : '' }}"
                wire:click="selectNote('{{ $note->id }}')">
                <button wire:click.stop="delete('{{ $note->id }}')"
                    class="icon-danger absolute top-2 right-2"
                    aria-label="{{ __('Delete this note') }}"
                    title="Supprimer cette catégorie">
                    <flux:icon.trash class="w-5 h-5" variant="micro" />
                </button>
                <p class="mt-2 text-sm">
                    @php
                        $excerpt = Str::limit($note->content, 50);
                        foreach (['=', '-'] as $delim) {
                            if (Str::contains($excerpt, $delim)) {
                                $excerpt = Str::before($excerpt, $delim);
                            }
                        }
                    @endphp
                <h3 class="font-bold text-xl">{{ $excerpt }}</h3>


            </div>
        @empty
            <div class="my-auto mx-auto">
                {{ __('You have no notes yet.') }}
            </div>
        @endforelse
    </div>

    <div class="flex-1 ">
        <livewire:note.show :note="$selectedNote" wire:key="note-{{ $selectedNote?->id }}" />
    </div>
</div>
