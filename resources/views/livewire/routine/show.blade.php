<div class="bg-custom-accent grid grid-cols-2 h-[71vh]">
    <!-- Colonne gauche : infos tâche + timer -->
    <div class="col-span-1 p-6 flex flex-col justify-center"
    x-data="timer(@json($routine->tasks->pluck('duration')->toArray()))" x-init="init()">
        <h2 class="title-color text-center mb-6 font-semibold">
            @if ($isFinished)
                {{ __('Routine completed') . ': ' . $routine->name }}
            @elseif ($currentTaskIndex === null)
            @else
                {{ __('Current Task') . ': ' . ($currentTask ? $currentTask->name : '') }}
            @endif
        </h2>

        <!-- Not started state - Routine information -->
        @if ($currentTaskIndex === null && !$isFinished)
            <div class="bg-custom p-6 rounded-lg shadow-md mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold title-color">{{ $routine->name }}</h3>
                    <span class="bg-elix/20 text-elix py-1 px-3 rounded-full text-sm">
                        {{ $routine->tasks->count() }} {{ __('tasks') }}
                    </span>
                </div>

                <div class="space-y-4">
                    @if ($routine->description)
                        <div class="rounded-lg border-color p-3 italic">
                            {{ $routine->description }}
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <flux:icon.clock class="text-elix" aria-hidden="true" />
                        <span>{{ __('Total duration') }}: {{ gmdate('H:i:s', $routine->tasks->sum('duration')) }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:icon.calendar class="text-elix" aria-hidden="true" />
                        <span>{{ __('Created') }}: {{ $routine->created_at->format('d M Y') }}</span>
                    </div>

                    @if ($routine->completed_count > 0)
                        <div class="flex items-center gap-2">
                            <flux:icon.check-circle class="text-elix" aria-hidden="true" />
                            <span>{{ __('Completed') }}: {{ $routine->completed_count }} {{ __('times') }}</span>
                        </div>

                        @if ($routine->last_completed_at)
                            <div class="flex items-center gap-2">
                                <flux:icon.arrow-path class="text-elix" aria-hidden="true" />
                                <span>{{ __('Last completed') }}:
                                    {{ $routine->last_completed_at->diffForHumans() }}</span>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="mt-6">
                    <flux:button wire:click="start" variant="primary" class="w-full">
                        <flux:icon.play variant="micro" class="w-5 h-5 inline-block mr-1" aria-hidden="true" />
                        {{ __('Start Routine') }}
                    </flux:button>
                </div>
            </div>
        @endif

        <!-- Finished state - Completion celebration -->
        @if ($isFinished)
            <div class="bg-custom p-6 rounded-lg shadow-md mb-6 text-center">
                <div class="flex flex-col items-center justify-center space-y-4">
                    <!-- Celebration animation -->
                    <div class="celebration-icon text-elix mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="w-24 h-24 animate-bounce" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <h3 class="text-2xl font-bold title-color">{{ __('Congratulations!') }}</h3>
                    <p class="text-lg">{{ __('You have successfully completed your routine') }}</p>

                    <div class="stats grid grid-cols-2 gap-4 w-full mt-4">
                        <div class="stat bg-elix/10 p-4 rounded-lg">
                            <div class="text-sm uppercase tracking-widest">{{ __('Total Time') }}</div>
                            <div class="font-mono text-2xl font-bold text-elix">
                                {{ gmdate('H:i:s', $routine->tasks->sum('duration')) }}</div>
                        </div>
                        <div class="stat bg-elix/10 p-4 rounded-lg">
                            <div class="text-sm uppercase tracking-widest">{{ __('Tasks Completed') }}</div>
                            <div class="font-mono text-2xl font-bold text-elix">{{ $routine->tasks->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Timer view for active task -->
        @if ($currentTaskIndex !== null && !$isFinished)
            <div class="relative w-96 h-96 mx-auto mb-6">
                <svg viewBox="0 0 100 100" class="absolute inset-0 w-full h-full" aria-hidden="true">
                    <circle cx="50" cy="50" r="45" :stroke-dasharray="circum"
                        :stroke-dashoffset="circum - (percent() / 100) * circum" stroke-linecap="round"
                        class="transform -rotate-90 origin-center stroke-color stroke-4 fill-transparent" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center space-y-3">
                    <div class="text-center">
                        <div class="text-sm uppercase tracking-widest">{{ __('Elapsed Time') }}</div>
                        <div class="font-mono text-4xl font-bold text-elix" x-text="hhmmss(elapsedAllMs())"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-widest">
                            {{ __('Remaining (Current Task)') }}</div>
                        <div class="font-mono text-2xl font-semibold title-color" x-text="hhmmss(remainingMs)"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-widest">{{ __('Total Remaining') }}</div>
                        <div class="font-mono text-2xl font-semibold title-color" x-text="hhmmss(totalRemainingMs())">
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-widest">{{ __('Progress') }}</div>
                        <div class="text-lg font-semibold text-elix"
                            x-text="Math.min(Math.floor(percent()), 100) + '%'"></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-center gap-2">
                <flux:button wire:click="playPause" variant="primary">
                    @if ($isPaused)
                        <flux:icon.play variant="micro" class="w-5 h-5 inline-block mr-1" aria-hidden="true" />
                        {{ __('Resume') }}
                    @else
                        <flux:icon.pause variant="micro" class="w-5 h-5 inline-block mr-1" aria-hidden="true" />
                        {{ __('Pause') }}
                    @endif
                </flux:button>
                <flux:button wire:click="stop" variant="danger">
                    <flux:icon.stop variant="micro" class="w-5 h-5 inline-block mr-1" aria-hidden="true" />
                    {{ __('Stop') }}
                </flux:button>
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
                    @if ($loop->index < $currentTaskIndex) border-color
                    @elseif ($loop->index === $currentTaskIndex) bg-color border-color
                    @else border-grey @endif">
                </span>

                @unless ($loop->last)
                    <span
                        class="flex-1 {{ $loop->index < $currentTaskIndex ? 'border-color' : 'border-grey' }} border-dashed border-2">
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
                @if ($currentTaskIndex !== null && !$isFinished)
                    <flux:button wire:click="next" variant="primary" class="w-full">
                        <flux:icon.check variant="micro" class="w-5 h-5 inline-block mr-1" aria-hidden="true" />
                        {{ __('Complete Current Task') }}
                    </flux:button>
                @endif
            </div>

            <div x-ref="list" class="flex-1 overflow-y-scroll p-4 space-y-4">
                @foreach ($routine->tasks as $task)
                    <div id="{{ $task->id }}" wire:key="task-{{ $task->id }}"
                        class="bg-custom p-4 rounded-md shadow-sm flex flex-col justify-between
                            {{ $loop->index === $currentTaskIndex ? 'border-l-4 border-color' : '' }}
                            {{ $loop->index < $currentTaskIndex ? 'opacity-70' : '' }}">
                        <div class="flex items-center space-x-4">
                            <div class="w-full">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-bold">
                                        {{ $task->name }}
                                    </h3>

                                    @if ($currentTaskIndex === null && !$isFinished)
                                        <div class="flex items-center ml-auto">
                                            <livewire:routine-task.form :routine="$routine" :task="$task"
                                                wire:key="task-form-{{ $task->id }}" />
                                            <flux:icon.trash class="cursor-pointer ml-2" variant="micro"
                                                wire:click="deleteTask('{{ $task->id }}')"
                                                wire:key="delete-task-{{ $task->id }}" role="button" tabindex="0" aria-label="{{ __('Delete task') }}" aria-hidden="true" />
                                            <flux:icon.document-duplicate class="cursor-pointer ml-2" variant="micro"
                                                wire:click="duplicateTask('{{ $task->id }}')"
                                                wire:key="duplicate-task-{{ $task->id }}" role="button" tabindex="0" aria-label="{{ __('Duplicate task') }}" aria-hidden="true" />
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center align-middle my-2">
                                    @if ($task->description)
                                        <div class="flex items-center">
                                            <flux:icon.flag class="text-elix" aria-hidden="true" />
                                            <span class="mx-2">
                                                @limit($task->description, 120)
                                            </span>
                                        </div>
                                    @endif
                                    <div class="ml-auto my-auto flex items-center">
                                        @if ($currentTaskIndex === null && !$isFinished)
                                            <button type="button" class="drag-handle cursor-move" aria-label="{{ __('Reorder task') }}">
                                                <flux:icon.bars-4 class="" aria-hidden="true" />
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="flex items-center">
                                <flux:icon.clock class="" aria-hidden="true" />
                                <span class="ml-2">{{ gmdate('H:i:s', $task->duration) }}</span>
                            </div>

                            @if ($loop->index === $currentTaskIndex)
                                <flux:button wire:click="next" variant="primary" class="ml-auto">
                                    <flux:icon.arrow-right variant="micro" class="w-5 h-5 inline-block mr-1" aria-hidden="true" />
                                    {{ __('Complete') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($currentTaskIndex === null && !$isFinished)
                    <div
                        class="bg-custom-accent hover:bg-custom-accent/70 p-4 rounded-md border-2 border-dashed flex items-center justify-center text-center cursor-pointer transition-all" role="button" tabindex="0" aria-label="{{ __('Add new task') }}">
                        <livewire:routine-task.form :routine="$routine" wire:key="task-form-create" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

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
                return this.totalDuration - (this.remainingMs + this.upcomingMs);
            },

            totalRemainingMs() {
                return this.remainingMs + this.upcomingMs;
            },

            percent() {
                const elapsed = this.elapsedAllMs();
                // Clamp entre 0 et 100
                if (elapsed <= 0) return 0;
                if (elapsed >= this.totalDuration) return 100;
                return (elapsed / this.totalDuration) * 100;
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
