<div class="relative inline-block text-left">
    <div> {{ __('Selected language') }}:
        {{ config('app.supported_locales')[$locale] }}
    </div>
    <flux:select label="Langue">
        @foreach (config('app.supported_locales') as $lang => $label)
            @if ($lang !== $locale)
                <flux:select.option :value="$lang" :label="$label"
                    wire:click="switchTo('{{ $lang }}')" />
            @endif
        @endforeach
    </flux:select>
</div>
