<div class="">
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-48">
        <div
            class="flex-shrink-0 w-1/4 bg-custom-accent p-6 shadow-sm hover transition-shadow flex items-center justify-center cursor-pointer">
            <div>
                <livewire:routine.form wire:key="routine-form-create" />
            </div>
        </div>

        {{-- Liste des routines existantes --}}
        @forelse($routines as $routine)
            <div class="flex-shrink-0 w-1/4 bg-custom-accent p-6 shadow-sm hover relative cursor-pointer {{ $selectedRoutine?->id === $routine->id ? 'border-color' : '' }}"
                wire:click="selectRoutine('{{ $routine->id }}')" wire:key="routine-{{ $routine->id }}">
                <div class="absolute top-4 right-4" x-data="{ open: false }" x-init="$watch('open', value => { if (!value) $dispatch('close-all') })"
                    @close-all.window="open = false">
                    <div class="relative">
                        <button @click="open = !open" class="cursor-pointer rounded-lg">
                            <flux:icon.ellipsis-vertical />
                        </button>
                        <div x-show="open" @click.away="open = false" @keydown.escape.window="open = false" @click.stop
                            class="absolute right-0 mt-2 w-32 bg-custom-accent rounded-lg shadow-lg z-10" wire:ignore>
                            <livewire:routine.form :routine="$routine" :wire:key="'routine-form-'.$routine->id" lazy />
                            <button wire:click="delete('{{ $routine->id }}')" @click="open = false"
                                class="block w-full px-2 py-2 text-sm text-danger-500 hover rounded-b-lg">
                                {{ __('Delete') }} <span class="inline-flex items-center ml-2">
                                    <flux:icon.trash variant="micro" />
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <h3 class="text-xl font-semibold">{{ $routine->name }}</h3>
                <p class="mt-2 text-sm">@limit($routine->description, 120)</p>
                @if ($routine->tasks->count())
                    <div class="mt-4 space-y-2">
                        @foreach ($routine->tasks->take(0) as $task)
                            <div class="rounded-xl p-3">
                                <h4 class="font">{{ $task->name }}</h4>
                                <p class="text-sm 0">@limit($task->description, 10)</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="my-auto mx-auto">
                {{ __('You don\'t have any routines at the moment...') }}
            </div>
        @endforelse
    </div>
    {{-- h-[50vh] --}}
    <div class="flex-1 ">
        @if ($selectedRoutine)
            <livewire:routine.show :routine="$selectedRoutine" wire:key="routine-show-{{ $selectedRoutine->id }}" />
        @endif
    </div>
</div>
