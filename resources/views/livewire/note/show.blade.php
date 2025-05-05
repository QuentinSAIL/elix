<div class="">
    <div x-data="{ markdownContent: @js($markdownContent ?? '') }" class="flex flex-row h-full overflow-y-scroll">
        <div class="py-3 flex-1 flex flex-col">
            <textarea x-model="markdownContent" class="w-full flex-1 p-4 mt-2 focus:outline-none resize-none"
                :placeholder="__('Your note content here')" wire:change.lazy="save"></textarea>
        </div>

        <!-- Divider with a big border -->
        <div class="border-l-4 border-gray-300 mx-4"></div>

        <div class="p-3 flex-1 flex flex-col">
            <div class="w-full flex-1 p-2 mt-2 overflow-y-auto">
                <div x-init="$watch('markdownContent', value => $wire.set('markdownContent', value))">
                    @markdom($markdownContent ?? __('Your note is empty.'))
                </div>
            </div>
        </div>
    </div>
    <div class="text-right mr-2">
        <flux:button variant="primary" wire:click="save" class="bg-custom-accent">
            {{ __('Save') }}
        </flux:button>
    </div>
</div>
