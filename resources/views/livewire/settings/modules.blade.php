<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Modules')" :subheading="__('Activate or deactivate the modules you need')">
        <div class="space-y-6 p-6">
            @foreach($allModules as $module)
                <flux:checkbox.group wire:model="activeModules" label="Modules">
                    <flux:checkbox
                        :checked="in_array($module->id, $activeModules)"
                        value="{{ $module->id }}"
                        label="{{ $module->name }}"
                        description="{{ $module->description }}"
                    />
                </flux:checkbox.group>
            @endforeach

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="updateModules">
                    {{ __('Save') }}
                </flux:button>
            </div>

        </div>
    </x-settings.layout>
</section>
