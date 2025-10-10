<div class="h-[64vh] flex flex-row overflow-y-scroll bg-custom-accent">
    <div class="py-3 flex-1 flex flex-col">
        <textarea wire:model.live.debounce.750ms="markdownContent"
            class="w-full flex-1 p-4 mt-2 focus:outline-none resize-none" aria-label="{{ __('Note content') }}"></textarea>
    </div>

    <div class="border-x border-zinc-700 mx-4"></div>
    <div class="p-3 flex-1 flex flex-col">
        <div class="w-full flex-1 p-2 mt-2 overflow-y-auto">
            @if ($markdownContent)
                @markdom($markdownContent)
            @else
                <div>{{ __('Your note is empty.') }}</div>
            @endif
        </div>
    </div>
</div>
