<div>
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-96">
        <div
            class="flex-shrink-0 w-1/4 bg-custom p-6 shadow-sm hover:shadow-md transition-shadow flex items-center justify-center">
            <flux:modal.trigger name="create-routine"
                class="w-full h-full flex items-center justify-center bg-custom-accent hover-custom rounded-2xl cursor-pointer">
                <span class="m-1">Ajouter une routine</span>
                <flux:icon.plus class="text-2xl text-white" />
            </flux:modal.trigger>
            <flux:modal name="create-routine" class="w-5/6">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="2xl">Créez votre routine</flux:heading>
                        <flux:text class="mt-2">
                            Remplissez les champs ci-dessous pour configurer votre nouvelle routine.
                        </flux:text>
                    </div>

                    <flux:input label="Nom" placeholder="Le nom de votre routine" wire:model="newRoutine.name" />

                    <flux:textarea label="Description" placeholder="Une brève description de la routine"
                        wire:model="newRoutine.description" />

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="Date de début" type="date" wire:model="newRoutine.start_datetime" />
                        <flux:input label="Date de fin" type="date" wire:model="newRoutine.end_datetime" />
                    </div>

                    <flux:select wire:model="newRoutine.frenquency" placeholder="Choisissez uune fréquence" label="Fréquence">
					@foreach($frequencies as $frequency)
						<flux:select.option value="{{ $frequency->id }}">{{ $frequency->name }}</flux:select.option>
					@endforeach
                        {{-- <flux:select.option>Photography</flux:select.option>
                        <flux:select.option>Design services</flux:select.option>
                        <flux:select.option>Web development</flux:select.option>
                        <flux:select.option>Accounting</flux:select.option>
                        <flux:select.option>Legal services</flux:select.option>
                        <flux:select.option>Consulting</flux:select.option>
                        <flux:select.option>Other</flux:select.option> --}}

                    </flux:select>

                    <flux:switch label="Active" wire:model="newRoutine.is_active" />

                    <div class="flex mt-16">
                        <flux:spacer />
                        <flux:button wire:click="create()" variant="primary">
                            Créer la routine
                        </flux:button>
                    </div>
                </div>

            </flux:modal>

        </div>
        @forelse($routines as $routine)
            <div class="flex-shrink-0 w-1/4 bg-custom p-6 shadow-sm hover:shadow-md transition-shadow relative">
                <div wire:click="delete('{{ $routine->id }}')"
                    class="cursor-pointer absolute top-4 right-4 hover-custom hover:text-red-600">
                    <flux:icon.x-mark />
                </div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $routine->name }}</h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $routine->description }}</p>
                @if ($routine->tasks->count())
                    <div class="mt-4 space-y-4">
                        @foreach ($routine->tasks as $task)
                            @if ($loop->index < 2)
                                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4">
                                    <h4 class="font-medium text-zinc-800 dark:text-zinc-200">{{ $task->name }}</h4>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">@limit($task->description, 200)</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="flex-shrink-0 w-4">Vous n'avez aucune routine pour le moment...</div>
        @endforelse

    </div>
</div>
