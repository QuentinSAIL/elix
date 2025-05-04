<div class="grid grid-cols-2">
    <div class="col-span-1 p-6">

        <h2 class="text-2xl text-center mb-6">
            {{ $currentTaskIndex === null ? 'Bienvenue dans les détails de la routine ' . $routine->name : 'Tâche en cours' }}
        </h2>

        @if ($currentTaskIndex !== null && $currentTask)
            <div class="space-y-4 text-center">
                <div class="font-bold text-xl">{{ $currentTask->name }}</div>

                <div>
                    <span class="font-bold uppercase text-sm tracking-widest">Temps restant</span>
                    <div class="mt-2 inline-block">
                        <span id="remaining" class="font-mono text-7xl border-4 rounded-2xl inline-block px-8 py-4">
                            00:00:00
                        </span>
                    </div>
                </div>

                <div>
                    <span class="font-bold uppercase text-sm tracking-widest">Temps total restant</span>
                    <div class="mt-2 inline-block">
                        <span id="remaining-total" class="font-mono text-3xl border-2 rounded inline-block px-4 py-2">
                            00:00:00
                        </span>
                    </div>
                </div>
            </div>
        @else
            {{-- … si pas démarré … --}}
        @endif
    </div>

    <div class="col-span-1 grid grid-cols-10 gap-4 overflow-y-scroll h-128">
        <div class="col-span-2 flex flex-col items-center mt-32">
            @foreach ($routine->tasks as $task)
                <span
                    class="w-5 h-5 rounded-full border-3
          @if ($loop->index < $currentTaskIndex) bg-elix border-elix
          @else border-zinc-100 @endif">
                </span>

                @if (!$loop->last)
                    <span
                        class="flex-1 {{ $loop->index < $currentTaskIndex ? 'border-elix' : 'border-zinc-300' }} border-dashed border-2">
                    </span>
                @else
                    <span class="flex-1 mb-8"></span>
                @endif
            @endforeach
        </div>

        <div class="col-span-8 flex flex-col" x-data="{
            tasks: @js($routine->tasks->map->id),
            init() {
                new Sortable(this.$refs.list, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: evt => {
                        this.tasks.splice(evt.newIndex, 0,
                            this.tasks.splice(evt.oldIndex, 1)[0]
                        );
                        $wire.updateTaskOrder(this.tasks);
                    },
                });
            },
        }">
            <div class="flex items-end justify-end m-4 gap-1">
                {{-- Démarrer / Reprendre / Pause / Arrêter --}}
            </div>

            <div x-ref="list" class="flex-1 overflow-y-auto p-4 space-y-4">
                @foreach ($routine->tasks as $task)
                    <div
                        class="bg-custom-accent p-4 h-48 flex items-center justify-between
               {{ $loop->index === $currentTaskIndex ? 'border-3 border-elix' : '' }}">
                        <div class="flex items-center space-x-4">
                            {{-- handle seulement si la routine n'a pas démarré --}}
                            @if ($currentTaskIndex === null)
                                <button type="button" class="drag-handle cursor-move">
                                    <flux:icon.bars-4 class="w-6 h-6 text-zinc-300 hover:text-zinc-500" />
                                </button>
                            @endif

                            <div>
                                <h3 class="text-lg font-bold">{{ $task->name }} - {{ $task->order }}</h3>
                                <div class="flex items-center mt-2">
                                    <flux:icon.flag class="text-elix" />
                                    <span class="ml-2 text-white">@limit($task->description, 100)</span>
                                </div>
                                <div class="flex items-center mt-1 text-custom-inverse">
                                    <flux:icon.clock class="text-white" />
                                    <span class="ml-2">{{ $task->duration }}s</span>
                                </div>
                            </div>
                        </div>

                        @if ($loop->index === $currentTaskIndex)
                            <flux:button wire:click="next" variant="primary" class="mt-4">
                                {{ __('Done') }}
                            </flux:button>
                        @endif
                    </div>
                @endforeach

                <div
                    class="bg-custom hover-custom p-4 h-24 border-3 border-dashed flex items-center
             justify-center text-center cursor-pointer">
                    {{ __('Ajouter une tâche') }}
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        const durations = @json($routine->tasks->pluck('duration')).map(d => Number(d));
        let endTime = 0;
        let rafId = null;
        let currentIndex = 0;
        let remainingMs = 0;

        /**
         * Transforme un total de secondes en hh:mm:ss,
         * avec deux chiffres par partie (00 à 99).
         */
        function formatTime(sec) {
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            return [
                String(h).padStart(2, '0'),
                String(m).padStart(2, '0'),
                String(s).padStart(2, '0'),
            ].join(':');
        }

        /** Met à jour l’affichage figé ou en pause */
        function updateDisplay(remMs) {
            const secCurrent = Math.ceil(remMs / 1000);
            const secFollowing = durations
                .slice(currentIndex + 1)
                .reduce((sum, d) => sum + d, 0);
            const secTotal = secCurrent + secFollowing;

            const remEl = document.getElementById('remaining');
            const totalEl = document.getElementById('remaining-total');
            if (remEl) remEl.textContent = formatTime(secCurrent);
            if (totalEl) totalEl.textContent = formatTime(secTotal);
        }

        /** Boucle d’animation du timer */
        function update() {
            const now = Date.now();
            let diff = endTime - now;
            if (diff < 0) diff = 0;

            updateDisplay(diff);

            if (diff > 0) {
                rafId = requestAnimationFrame(update);
            } else {
                Livewire.dispatch('timer-finished');
            }
        }

        Livewire.on('start-timer', ([{
            duration = 0,
            currentIndex: idx = 0
        } = {}]) => {
            currentIndex = idx;
            remainingMs = duration * 1000;
            endTime = Date.now() + remainingMs;
            if (rafId) cancelAnimationFrame(rafId);
            update();
        });

        Livewire.on('stop-timer', () => {
            if (rafId) cancelAnimationFrame(rafId);
            remainingMs = 0;
            updateDisplay(0);
        });

        Livewire.on('play-pause', ([{
            isPaused: pause
        } = {}]) => {
            if (pause) {
                // PAUSE : gèle l’affichage
                if (rafId) cancelAnimationFrame(rafId);
                remainingMs = Math.max(endTime - Date.now(), 0);
                updateDisplay(remainingMs);
            } else {
                // REPRISE : remet endTime et relance
                endTime = Date.now() + remainingMs;
                if (rafId) cancelAnimationFrame(rafId);
                update();
            }
        });
    });
</script>
