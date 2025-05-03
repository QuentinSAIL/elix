<div class="grid grid-cols-2">
    <!-- Colonne rouge : infos tâche + timer -->
    <div class="col-span-1 bg-red-400 p-4">
        <h2 class="text-2xl text-center mb-6 text-custom-inverse">
            {{ $currentTaskIndex === null ? 'Bienvenue dans les détails de la routine' : 'Tâche en cours' }}
        </h2>

        @if ($currentTaskIndex !== null && $currentTask)
            <div class="space-y-2 text-center">
                <div class="font-bold text-elix">{{ $currentTask->name }}</div>
                <div>
                    Temps restant :
                    <span id="remaining" class="font-mono text-xl text-custom-inverse">
                        {{ $currentTask->duration }}
                    </span>s
                </div>
            </div>
        @else
            <div class="text-center font-bold text-elix">
                {{ $routine->name }}
            </div>
        @endif
    </div>

    <!-- Colonne de droite : timeline & contenu -->
    <div class="col-span-1 grid grid-cols-10 gap-4 overflow-y-scroll h-128">
        <!-- Timeline -->
        <div class="col-span-2 flex flex-col items-center mt-32">
            @foreach ($routine->tasks as $task)
                <span
                    class="w-5 h-5 rounded-full border-3
          @if ($loop->index < $currentTaskIndex) bg-elix border-elix
          @else border-zinc-100 @endif">
                </span>

                @if (!$loop->last)
                    <span
                        class="flex-1 {{ $loop->index < $currentTaskIndex ? 'border-elix' : 'border-zinc-300 dark:border-zinc-700' }} border-dashed border-2">
                    </span>
                @else
                    <span class="flex-1 mb-8"></span>
                @endif
            @endforeach
        </div>

        <!-- Contenu des tâches -->
        <div class="col-span-8 flex flex-col">
            <div class="flex items-end justify-end m-4">
                <flux:button wire:click="start" variant="primary">
                    {{ $currentTaskIndex === null ? 'Démarrer' : 'Recommencer' }}
                </flux:button>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                @forelse ($routine->tasks as $task)
                    <div
                        class="bg-custom-accent p-4 m-4 h-48 {{ $loop->index === $currentTaskIndex ? 'border-3 border-elix' : '' }}">
                        <h3 class="text-lg font-bold">{{ $task->name }}</h3>

                        <div class="flex items-center mt-2">
                            <flux:icon.flag class="text-elix" />
                            <span class="ml-2 text-white">@limit($task->description, 100)</span>
                        </div>

                        <div class="flex items-center mt-1 text-custom-inverse">
                            <flux:icon.clock class="text-white" />
                            <span class="ml-2">
                                {{ $task->duration }}s
                            </span>
                        </div>

                        @if ($loop->index === $currentTaskIndex)
                            <flux:button wire:click="next" variant="primary" class="mt-4">
                                {{ __('Done') }}
                            </flux:button>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">Aucune tâche trouvée pour cette routine.</p>
                @endforelse

                <div
                    class="bg-custom hover-custom p-4 m-4 h-24 border-3 border-dashed flex items-center justify-center text-center cursor-pointer">
                    {{ __('Ajouter une tâche') }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        let timerIntervalId = null;
        console.log('Timer initialized');

        function updateRemainingTime(duration) {
            console.log('Updating remaining time:', duration);
            const remEl = document.getElementById('remaining');
            if (remEl) {
                remEl.textContent = duration;
            } else {
                console.warn('Remaining time element not found');
            }
        }

        Livewire.on('start-timer', (event) => {
            let remaining = event[0].duration || 0;
            updateRemainingTime(remaining);

            if (timerIntervalId) {
                console.log('Clearing existing timer interval');
                clearInterval(timerIntervalId);
            }

            timerIntervalId = setInterval(() => {
                console.log('Timer tick, remaining:', remaining);
                if (remaining > 0) {
                    remaining--;
                    updateRemainingTime(remaining);
                } else {
                    console.log('Timer finished, clearing interval');
                    clearInterval(timerIntervalId);
                    Livewire.dispatch('timerFinished');
                }
            }, 1000);
        });

        Livewire.on('stop-timer', () => {
            console.log('Received stop-timer event');
            if (timerIntervalId) {
                console.log('Clearing timer interval');
                clearInterval(timerIntervalId);
            }
            updateRemainingTime(0);
        });
    });
</script>
