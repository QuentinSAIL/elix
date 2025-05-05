<div class="h-screen">
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-48">
        <div class="flex-shrink-0 w-1/4 h-full bg-custom p-6 shadow-sm hover:shadow-md transition-shadow flex items-center justify-center cursor-pointer {{ $selectedNote?->id === null ? 'border-elix border-2' : '' }}"
            wire:click="selectNote('{{ null }}')">
            <span class="m-1">
                {{ __('Add new note') }}
            </span>
            <flux:icon.plus class="text-2xl text-white" />
        </div>

        @forelse($notes as $note)
            <div class="flex-shrink-0 w-1/4 h-full bg-custom p-6 shadow-sm hover:shadow-md transition-shadow relative cursor-pointer {{ $selectedNote?->id === $note->id ? 'border-elix border-2' : '' }}"
                wire:click="selectNote('{{ $note->id }}')">
                <div wire:click.stop="delete('{{ $note->id }}')"
                    class="cursor-pointer absolute top-4 right-4 hover:text-red-600 rounded-lg z-10">
                    <flux:icon.trash />
                </div>
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

    <div class="bg-custom flex-1 overflow-y-scroll h-3/4">
        <livewire:note.show :note="$selectedNote" wire:key="note-{{ $selectedNote?->id }}" />
    </div>
</div>
