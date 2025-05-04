<div class="grid grid-cols-2">
    <!-- Colonne gauche : infos tâche + timer -->
    <div class="col-span-1 p-6">
        <h2 class="text-2xl text-center mb-6">
            {{ $currentTaskIndex === null ? 'Bienvenue dans les détails de la routine ' . $routine->name : 'Tâche en cours' }}
        </h2>

        @if ($currentTaskIndex !== null && $currentTask)
            <div class="space-y-4 text-center">
                {{-- Nom de la tâche --}}
                <div class="font-bold text-xl">{{ $currentTask->name }}</div>

                {{-- Timer de la tâche --}}
                <div>
                    <span class="font-bold uppercase text-sm tracking-widest">Temps restant</span>
                    <div class="mt-2 inline-block">
                        <span id="remaining" class="font-mono text-7xl border-4 rounded-2xl inline-block px-8 py-4">
                            00:00
                        </span>
                    </div>
                </div>

                {{-- **NOUVEAU** Timer total restant --}}
                <div>
                    <span class="font-bold uppercase text-sm tracking-widest">Temps total restant</span>
                    <div class="mt-2 inline-block">
                        <span id="remaining-total" class="font-mono text-3xl border-2 rounded inline-block px-4 py-2">
                            00:00
                        </span>
                    </div>
                </div>
            </div>
        @else
            {{-- … si pas démarré … --}}
        @endif
    </div>

    <!-- Colonne droite : timeline & contenu -->
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
                        class="flex-1 {{ $loop->index < $currentTaskIndex ? 'border-elix' : 'border-zinc-300' }} border-dashed border-2">
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
                            <span class="ml-2 text-white">
                                @limit($task->description, 100)
                            </span>
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
    // 1) Récupère et convertit en nombres
    const durations = @json($routine->tasks->pluck('duration'))
        .map(d => Number(d));

    let endTime = 0;
    let rafId = null;
    let currentIndex = 0;

    function formatTime(sec) {
        const m = String(Math.floor(sec / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        return `${m}:${s}`;
    }

    function update() {
        const now = Date.now();
        let diff = endTime - now;
        if (diff < 0) diff = 0;

        // Temps restant sur la tâche courante
        const secCurrent = Math.ceil(diff / 1000);

        // Somme des durées (en secondes) des tâches suivantes
        const secFollowing = durations
            .slice(currentIndex + 1)
            .reduce((sum, d) => sum + Number(d), 0);

        // Total restant = tâche courante + tâches suivantes
        const secTotal = secCurrent + secFollowing;

        // Mise à jour du DOM
        const remEl = document.getElementById('remaining');
        const totalEl = document.getElementById('remaining-total');
        if (remEl)   remEl.textContent   = formatTime(secCurrent);
        if (totalEl) totalEl.textContent = formatTime(secTotal);

        if (diff > 0) {
            rafId = requestAnimationFrame(update);
        } else {
            Livewire.dispatch('timer-finished');
        }
    }

    Livewire.on('start-timer', ([payload = {}]) => {
        const { duration = 0, currentIndex: idx = 0 } = payload;
        currentIndex = idx;
        endTime = Date.now() + duration * 1000;

        if (rafId) cancelAnimationFrame(rafId);
        update();
    });

    Livewire.on('stop-timer', () => {
        if (rafId) cancelAnimationFrame(rafId);
        ['remaining', 'remaining-total'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = formatTime(0);
        });
    });
});
</script>
