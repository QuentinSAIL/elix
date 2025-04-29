<x-layouts.app.sidebar :title="$title ?? null" clas=''>
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
