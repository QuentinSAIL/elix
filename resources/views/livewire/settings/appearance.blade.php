<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun" aria-hidden="true">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon" aria-hidden="true">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop" aria-hidden="true">{{ __('System') }}</flux:radio>
        </flux:radio.group>
        <livewire:settings.language-switcher />

    </x-settings.layout>
</section>
