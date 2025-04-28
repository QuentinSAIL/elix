<div class="flex flex-row gap-4 overflow-x-scroll py-4">
        <div class="flex-shrink-0 w-1/4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
        Ajouter une routine
        </div>
    @forelse($routines as $routine)
        <div class="flex-shrink-0 w-1/4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $routine->name }}</h3>
            <p class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $routine->description }}</p>
            @if($routine->tasks->count())
            <div class="mt-4 space-y-4">
                @foreach ($routine->tasks as $task)
                @if ($loop->index < 2)
                    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4">
                    <h4 class="font-medium text-zinc-800 dark:text-zinc-200">{{ $task->name }}</h4>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $task->description }}</p>
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
