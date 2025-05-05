<div class="grid grid-cols-2 h-[74vh]">
    <!-- Colonne gauche : infos tâche + timer -->
    <div class="col-span-1 p-6">
        <h2 class="text-2xl text-center mb-6">
            {{ $currentTaskIndex === null ? __('Routine details') . $routine->name : __('Current Task') }}
        </h2>

        @if ($currentTaskIndex !== null && $currentTask)
            <div class="space-y-4 text-center">
                <div class="font-bold text-xl">{{ $currentTask->name }}</div>
                <div>
                    <span class="font-bold uppercase text-sm tracking-widest">{{ __('Remaining time') }}</span>
                    <div class="mt-2 inline-block">
                        <span id="remaining" class="font-mono text-7xl border-4 rounded-2xl inline-block px-8 py-4">
                            00:00:00
                        </span>
                    </div>
                </div>
                <div>
                    <span class="font-bold uppercase text-sm tracking-widest">
                        {{ __('Total remaining time') }}
                    </span>
                    <div class="mt-2 inline-block">
                        <span id="remaining-total" class="font-mono text-3xl border-2 rounded inline-block px-4 py-2">
                            00:00:00
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Colonne droite : timeline & contenu -->
    <div class="col-span-1 grid grid-cols-10 gap-4 overflow-y-scroll">
        <!-- Timeline -->
        <div class="col-span-2 flex flex-col items-center mt-32">
            @foreach ($routine->tasks as $task)
                <span
                    class="w-5 h-5 rounded-full border-3
                    @if ($loop->index < $currentTaskIndex) bg-elix border-elix
                    @else border-zinc-100 @endif">
                </span>

                @unless ($loop->last)
                    <span
                        class="flex-1 {{ $loop->index < $currentTaskIndex ? 'border-elix' : 'border-zinc-300' }} border-dashed border-2">
                    </span>
                @else
                    <span class="flex-1 mb-8"></span>
                @endunless
            @endforeach
        </div>

        <!-- Contenu des tâches draggable -->
        <div class="col-span-8 flex flex-col" wire:ignore.self x-data="{
            tasks: @js($routine->tasks->pluck('id')),
            init() {
                this.initSortable()
                Livewire.hook('message.processed', () => this.initSortable())
            },
            initSortable() {
                if (this.sortable) this.sortable.destroy()
                this.sortable = new Sortable(this.$refs.list, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: evt => {
                        this.tasks.splice(evt.newIndex, 0,
                            this.tasks.splice(evt.oldIndex, 1)[0]
                        )
                        this.$wire.updateTaskOrder(this.tasks)
                    },
                })
            },
        }">
            <div class="flex items-end justify-end m-4 gap-1">
                @if ($currentTaskIndex === null)
                    <flux:button wire:click="start" variant="primary">
                        <flux:icon.play variant="micro" class="w-5 h-5 inline-block mr-1" />
                        {{ __('Start') }}
                    </flux:button>
                @else
                    <flux:button wire:click="start" variant="primary">
                        <flux:icon.arrow-path variant="micro" class="w-5 h-5 inline-block mr-1" />
                        {{ __('Restart') }}
                    </flux:button>
                    <flux:button wire:click="playPause" variant="primary">
                        @if ($isPaused)
                            <flux:icon.play variant="micro" class="w-5 h-5 inline-block mr-1" />
                            {{ __('Resume') }}
                        @else
                            <flux:icon.pause variant="micro" class="w-5 h-5 inline-block mr-1" />
                            {{ __('Pause') }}
                        @endif
                    </flux:button>
                    <flux:button wire:click="stop" variant="danger">
                        <flux:icon.stop variant="micro" class="w-5 h-5 inline-block mr-1" />
                        {{ __('Stop') }}
                    </flux:button>
                @endif
            </div>

            <div x-ref="list" class="flex-1 overflow-y-auto p-4 space-y-4">
                @foreach ($routine->tasks as $task)
                    <div wire:key="task-{{ $task->id }}"
                        class="bg-custom-accent p-4 h-48 flex items-center justify-between
                               {{ $loop->index === $currentTaskIndex ? 'border-3 border-elix' : '' }}">
                        <div class="flex items-center space-x-4">
                            @if ($currentTaskIndex === null)
                                <button type="button" class="drag-handle cursor-move">
                                    <flux:icon.bars-4 class="w-6 h-6 text-zinc-300 hover:text-zinc-500" />
                                </button>
                            @endif

                            <div>
                                <h3 class="text-lg font-bold">
                                    {{ $task->name }} – {{ $task->order }}
                                </h3>

                                <div class="flex items-center mt-2">
                                    <livewire:routine-task.form :routine="$routine" :task="$task"
                                        wire:key="task-form-{{ $task->id }}" />
                                    <flux:icon.trash class="cursor-pointer ml-2" variant="micro"
                                        wire:click="deleteTask('{{ $task->id }}')" />
                                    <flux:icon.document-duplicate class="cursor-pointer ml-2" variant="micro"
                                        wire:click="duplicateTask('{{ $task->id }}')" />
                                </div>

                                <div class="flex items-center mt-2">
                                    <flux:icon.flag class="text-elix" />
                                    <span class="ml-2 text-white">
                                        @limit($task->description, 100)
                                    </span>
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
                    <livewire:routine-task.form :routine="$routine" wire:key="task-form-create" />
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

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
