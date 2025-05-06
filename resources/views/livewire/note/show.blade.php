<div class="h-[71vh] flex flex-row overflow-y-scroll">
    <div class="py-3 flex-1 flex flex-col">
        <textarea wire:model.live.debounce.750ms="markdownContent"
            class="w-full flex-1 p-4 mt-2 focus:outline-none resize-none"></textarea>
    </div>

    <div class="border-l-4 border-gray-300 mx-4"></div>
    <div class="p-3 flex-1 flex flex-col">
        <div class="w-full flex-1 p-2 mt-2 overflow-y-auto">
            @if ($markdownContent)
                @markdom($markdownContent)
            @else
                <div class="text-gray-400">{{ __('Your note is empty.') }}</div>
            @endif
        </div>
    </div>
</div>
