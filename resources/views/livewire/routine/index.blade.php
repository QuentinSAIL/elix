<div>
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-96">
        {{-- Bouton d’ouverture du modal --}}
        <div
            class="flex-shrink-0 w-1/4 bg-custom p-6 shadow-sm hover-custom transition-shadow flex items-center justify-center">
            <div>
                <livewire:routine.form />
            </div>
        </div>

        {{-- Liste des routines existantes --}}
        @forelse($routines as $routine)
            <div class="flex-shrink-0 w-1/4 bg-custom p-6 shadow-sm hover:shadow-md transition-shadow relative">
                <div class="absolute top-4 right-4" x-data="{ open: false }">
                    <div class="relative">
                        <button @click="open = !open" class="cursor-pointer hover-custom rounded-lg">
                            <flux:icon.ellipsis-vertical />
                        </button>
                        <div x-show="open" @click.away="open = false" @keydown.escape.window="open = false"
                            class="absolute right-0 mt-2 w-32 bg-custom-accent rounded-lg shadow-lg z-10">
                            <livewire:routine.form :routine="$routine" />
                            <button wire:click="delete('{{ $routine->id }}')" @click="open = false"
                                class="block w-full px-2 py-2 text-sm text-red-600 hover-custom rounded-b-lg">
                                Supprimer <span class="inline-flex items-center ml-2">
                                    <flux:icon.trash variant="micro" />
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <h3 class="text-xl font-semibold">{{ $routine->name }}</h3>
                <p class="mt-2 text-sm">{{ $routine->description }}</p>
                @if ($routine->tasks->count())
                    <div class="mt-4 space-y-2">
                        @foreach ($routine->tasks->take(2) as $task)
                            <div class="rounded-xl p-3 bg-custom-accent">
                                <h4 class="font">{{ $task->name }}</h4>
                                <p class="text-sm 0">@limit($task->description, 10)</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="my-auto mx-auto">
                Vous n'avez aucune routine pour le moment...
            </div>
        @endforelse
    </div>
</div>
