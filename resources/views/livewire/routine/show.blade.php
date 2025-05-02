{{-- resources/views/routines/show.blade.php --}}
<div class="grid grid-cols-2">
    <div class="col-span-1 bg-red-400">
        <h2 class="text-2xl text-center mb-6 text-custom-inverse">
            Bienvenue dans les détails de la routine
        </h2 text-center mb-6 text-custom-inverseh2>
        <span class="font-bold text-elix">{{ $routine->name }}</span>
    </div>

    <div class="col-span-1 grid grid-cols-10 gap-4">
        {{-- TIMELINE --}}
        <div class="col-span-2 flex flex-col items-center mt-24">

            @foreach ($routine->tasks as $task)
                {{-- Cercle --}}
                <span
                    class="w-5 h-5 rounded-full border-3
            @if ($loop->index <= $currentTaskIndex) bg-elix border-elix
            @else border-zinc-100 @endif">
                </span>

                @if (!$loop->last)
                    <span
                        class="flex-1 h-full {{ $loop->index < $currentTaskIndex ? 'border-elix' : 'border-zinc-300 dark:border-zinc-700' }} border-dashed border-2"></span>
                @else
                    <span class="flex-1 h-full"></span>
                @endif
            @endforeach
        </div>

        {{-- CARTES DE TÂCHES --}}
        <div class="col-span-8">
            @forelse($routine->tasks as $task)
                <div class="bg-custom-accent p-4 m-4 h-48">
                    {{-- Titre de la tâche --}}
                    <h3 class="text-lg font-bold">{{ $task->name }}</h3>

                    {{-- Description avec icône drapeau --}}
                    <div class="flex items-center mt-2">
                        <flux:icon.flag class="text-elix" />
                        <span class="ml-2 text-white">@limit($task->description, 100)</span>
                    </div>

                    {{-- Durée avec icône horloge --}}
                    <div class="flex items-center mt-1 text-custom-inverse">
                        <flux:icon.clock class="text-white" />
                        <span class="ml-2">
                            {{-- si tes durées sont stockées en secondes, tu peux convertir : --}}
                            {{ gmdate('i \m\i\n', $task->duration) }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-gray-500">Aucune tâche trouvée pour cette routine.</p>
            @endforelse

            {{-- Bouton Ajouter --}}
            <div
                class="bg-custom hover-custom p-4 m-4 h-24 border-3 border-dashed flex items-center justify-center text-center cursor-pointer">
                {{ __('Ajouter une tâche') }}
            </div>
        </div>
    </div>
</div>
