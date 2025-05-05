<div class="grid grid-cols-2 h-[74vh]">
    <!-- Colonne gauche : infos tâche + timer -->
    <div class="col-span-1 p-6" x-data="timer(@json($routine->tasks->pluck('duration')->toArray()))" x-init="init()">
        <h2 class="text-2xl text-center mb-6 font-semibold">
            @if ($isFinished)
                {{ __('Routine finished') . ' ' . $routine->name }}
            @elseif ($currentTaskIndex === null)
                {{ __('Routine details') . ' ' . $routine->name }}
            @else
                {{ __('Current Task') . ' ' . $currentTask->name }}
            @endif
        </h2>
        @if ($currentTaskIndex !== null && $currentTask)
            <div class="relative w-96 h-96 mx-auto mb-6">
                <svg viewBox="0 0 100 100" class="absolute inset-0 w-full h-full">
                    <circle cx="50" cy="50" r="45" :stroke-dasharray="circum"
                        :stroke-dashoffset="circum - (percent() / 100) * circum" stroke-linecap="round"
                        class="transform -rotate-90 origin-center stroke-elix stroke-4 fill-transparent" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center space-y-3">
                    <div class="text-center">
                        <div class="text-sm uppercase tracking-widest text-zinc-400">{{ __('Elapsed Time') }}</div>
                        <div class="font-mono text-4xl font-bold text-elix" x-text="hhmmss(elapsedAllMs())"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-widest text-zinc-500">{{ __('Remaining (Current Task)') }}</div>
                        <div class="font-mono text-2xl font-semibold text-white" x-text="hhmmss(remainingMs)"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-widest text-zinc-500">{{ __('Total Remaining') }}</div>
                        <div class="font-mono text-2xl font-semibold text-white" x-text="hhmmss(totalRemainingMs())"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-widest text-zinc-400">{{ __('Progress') }}</div>
                        <div class="text-lg font-semibold text-elix" x-text="Math.min(Math.floor(percent()), 100) + '%'"></div>
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

            <div x-ref="list" class="flex-1 overflow-y-scroll p-4 space-y-4">
                @foreach ($routine->tasks as $task)
                    <div id="{{ $task->id }}" wire:key="task-{{ $task->id }}"
                        class="bg-custom-accent p-4 h-48 flex flex-col justify-between {{ $loop->index === $currentTaskIndex ? 'border-elix' : '' }}">
                        <div class="flex items-center space-x-4">

                            <div class="w-full">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-bold">
                                        {{ $task->name }} – {{ $task->order }}
                                    </h3>

                                    @if ($currentTaskIndex === null)
                                        <div class="flex items-center ml-auto">
                                            <livewire:routine-task.form :routine="$routine" :task="$task"
                                                wire:key="task-form-{{ $task->id }}" />
                                            <flux:icon.trash class="cursor-pointer ml-2" variant="micro"
                                                wire:click="deleteTask('{{ $task->id }}')" />
                                            <flux:icon.document-duplicate class="cursor-pointer ml-2" variant="micro"
                                                wire:click="duplicateTask('{{ $task->id }}')" />
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center align-middle my-2 h-full">
                                    @if ($task->description)
                                        <div class="flex items-center">
                                            <flux:icon.flag class="text-elix" />
                                            <span class="mx-2 text-white">
                                                @limit($task->description, 120)
                                            </span>
                                        </div>
                                    @endif
                                    <div class="ml-auto my-auto flex items-center">
                                        @if ($currentTaskIndex === null)
                                            <button type="button" class="drag-handle cursor-move">
                                                <flux:icon.bars-4 class="text-zinc-300 hover:text-zinc-500" />
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center text-custom-inverse">
                            <div class="flex items-center">
                                <flux:icon.clock class="text-white" />
                                <span class="ml-2">{{ $task->duration }}s</span>
                            </div>

                            @if ($loop->index === $currentTaskIndex)
                                <flux:button wire:click="next" variant="primary" class="ml-auto">
                                    {{ __('Done') }}
                                </flux:button>
                            @endif
                        </div>

                    </div>
                @endforeach

                <div
                    class="bg-custom hover-custom p-4 h-24 border-3 border-dashed flex items-center justify-center text-center cursor-pointer">
                    <livewire:routine-task.form :routine="$routine" wire:key="task-form-create" />
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    function timer(durations) {
        const segments = durations.length;
        const r = 45;
        const circum = 2 * Math.PI * r;
        const totalDuration = durations.reduce((sum, d) => sum + d, 0) * 1000;

        return {
            durations,
            segments,
            circum,
            totalDuration,
            currentIndex: 0,
            remainingMs: 0,
            upcomingMs: 0,
            endTime: 0,
            rafId: null,

            init() {
                Livewire.on('start-timer', ([{
                    duration = 0,
                    currentIndex: idx = 0
                } = {}]) => {
                    this.currentIndex = idx;
                    this.remainingMs = duration * 1000;
                    this.upcomingMs = this.durations
                        .slice(idx + 1)
                        .reduce((sum, d) => sum + d, 0) * 1000;
                    this.endTime = Date.now() + this.remainingMs;
                    this.start();
                });
                Livewire.on('stop-timer', () => this.stop());
                Livewire.on('play-pause', ([{
                        isPaused: pause
                    } = {}]) =>
                    pause ? this.pause() : this.resume()
                );
            },

            start() {
                if (this.rafId) cancelAnimationFrame(this.rafId);
                this.rafId = requestAnimationFrame(() => this.update());
            },

            update() {
                const now = Date.now();
                const diff = this.endTime - now;
                this.remainingMs = diff > 0 ? diff : 0;
                if (diff > 0) {
                    this.rafId = requestAnimationFrame(() => this.update());
                } else {
                    Livewire.dispatch('timer-finished');
                }
            },

            pause() {
                if (this.rafId) cancelAnimationFrame(this.rafId);
                this.remainingMs = Math.max(this.endTime - Date.now(), 0);
            },

            resume() {
                this.endTime = Date.now() + this.remainingMs;
                this.start();
            },

            stop() {
                if (this.rafId) cancelAnimationFrame(this.rafId);
                this.remainingMs = 0;
            },

            elapsedAllMs() {
                return this.totalDuration - (this.remainingMs + this.upcomingMs) - 1000;
            },

            totalRemainingMs() {
                return this.remainingMs + this.upcomingMs;
            },

            percent() {
                const elapsed = this.elapsedAllMs();
                return this.totalDuration > 0 ?
                    (elapsed / this.totalDuration) * 100 :
                    0;
            },

            hhmmss(ms) {
                const sec = Math.ceil(ms / 1000);
                const h = Math.floor(sec / 3600).toString().padStart(2, '0');
                const m = Math.floor((sec % 3600) / 60)
                    .toString()
                    .padStart(2, '0');
                const s = (sec % 60).toString().padStart(2, '0');
                return `${h}:${m}:${s}`;
            },
        };
    }
</script>
