<div class="relative inline-block text-left">
    <flux:select label="{{ __('Language') }}" aria-label="{{ __('Select language') }}" :value="$locale">
        @foreach ($supportedLocales as $lang => $label)
            <flux:select.option :value="$lang" :label="$label" :selected="$locale === $lang"
                wire:click="switchTo('{{ $lang }}')" />
        @endforeach
    </flux:select>
</div>
