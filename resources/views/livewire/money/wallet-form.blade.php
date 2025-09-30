<div>
    <div class="space-y-6">
        <div>
            <flux:heading size="2xl">
                {{ $edition ? __('Edit wallet') : __('Create wallet') }}
            </flux:heading>
        </div>
        <form class="w-full text-left">
            <div class="space-y-6">
                <flux:input :label="__('Name')" wire:model.lazy="walletForm.name" />
                <flux:input :label="__('Unit')" wire:model.lazy="walletForm.unit" placeholder="EUR, BTC, ..." />
                <flux:input :label="__('Balance')" type="number" step="any" wire:model.lazy="walletForm.balance" />
            </div>

            <div class="flex mt-6 justify-end">
                <flux:button wire:click="save" variant="primary" wire:keydown.enter="save">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
