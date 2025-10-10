<div>
    <flux:fieldset>
        <flux:legend>{{ __('Preferred Currency') }}</flux:legend>
        <flux:description>{{ __('Choose your preferred currency for displaying financial values') }}</flux:description>
        
        <flux:radio.group variant="segmented" wire:model.live="currency">
            @foreach($supportedCurrencies as $code => $name)
                <flux:radio value="{{ $code }}" wire:click="switchTo('{{ $code }}')">
                    {{ $code }}
                </flux:radio>
            @endforeach
        </flux:radio.group>
    </flux:fieldset>
</div>
