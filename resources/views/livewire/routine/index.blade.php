<div class="">
    <div class="flex flex-row gap-4 overflow-x-auto py-4 h-48">
        <div
            class="flex-shrink-0 w-4/5 sm:w-1/2 lg:w-1/4 bg-custom-accent p-6 shadow-sm hover transition-shadow flex items-center justify-center cursor-pointer" role="button" tabindex="0" aria-label="{{ __('Add new routine') }}">
            <div>
                <livewire:routine.form wire:key="routine-form-create" />
            </div>
        </div>

        {{-- Liste des routines existantes --}}
        @forelse($routines as $routine)
            <div class="flex-shrink-0 w-4/5 sm:w-1/2 lg:w-1/4 bg-custom-accent p-6 shadow-sm hover relative cursor-pointer {{ $selectedRoutine?->id === $routine->id ? 'border-color' : '' }}"
                wire:click="selectRoutine('{{ $routine->id }}')" wire:key="routine-{{ $routine->id }}" role="button" tabindex="0" aria-label="{{ __('Select routine') }} {{ $routine->name }}">
                <div class="absolute top-4 right-4" x-data="{ open: false }" x-init="$watch('open', value => { if (!value) $dispatch('close-all') })"
                    @close-all.window="open = false">
                    <div class="relative">
                        <button @click="open = !open" class="cursor-pointer rounded-lg" aria-label="{{ __('Routine options') }}" aria-haspopup="true" x-bind:aria-expanded="open">
                            <flux:icon.ellipsis-vertical aria-hidden="true" />
                        </button>
                        <div x-show="open" @click.away="open = false" @keydown.escape.window="open = false" @click.stop
                            class="absolute right-0 mt-2 w-32 bg-custom-accent rounded-lg shadow-lg z-10" wire:ignore>
                            <livewire:routine.form :routine="$routine" :wire:key="'routine-form-'.$routine->id" lazy />
                            <button type="button" wire:click="delete('{{ $routine->id }}')" @click="open = false"
                                class="block w-full px-2 py-2 text-sm text-danger-500 hover rounded-b-lg" aria-label="{{ __('Delete routine') }}">
                                {{ __('Delete') }} <span class="inline-flex items-center ml-2">
                                    <flux:icon.trash variant="micro" aria-hidden="true" />
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <h3 class="text-xl font-semibold">@limit($routine->name, 12)</h3>
                <p class="mt-2 text-sm">@limit($routine->description, 60)</p>
                @if ($routine->tasks->count())
                    <div class="mt-4 space-y-2">
                        @foreach ($routine->tasks->take(0) as $task)
                            <div class="rounded-xl py-3">
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
    <div class="flex-1">
        @if ($selectedRoutine)
            <livewire:routine.show :routine="$selectedRoutine" wire:key="routine-show-{{ $selectedRoutine->id }}" />
        @endif
    </div>
</div>
