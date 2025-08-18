<div class="relative inline-block text-left">
    <div> {{ __('Selected language') }}:
        {{ config('app.supported_locales')[$locale] }}
    </div>
    <flux:select label="Langue" aria-label="{{ __('Select language') }}">
        @foreach ($supportedLocales as $lang => $label)
            <flux:select.option :value="$lang" :label="$label"
                wire:click="switchTo('{{ $lang }}')" />
        @endforeach
    </flux:select>
</div>
