<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="2xl">{{ __('Wallets') }}</flux:heading>
            <flux:text class="text-grey-inverse mt-1">{{ __('Manage your virtual wallets and balances') }}</flux:text>
        </div>
        <flux:modal.trigger name="create-wallet">
            <flux:button variant="primary" icon="plus" as="button">{{ __('New wallet') }}</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:modal name="create-wallet" class="w-5/6">
        <livewire:money.wallet-form />
    </flux:modal>

    @if ($wallets->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 rounded-xl bg-custom-ultra border border-custom-accent">
            <flux:icon.wallet class="w-10 h-10 text-grey-inverse mb-3" />
            <flux:heading size="lg">{{ __('No wallets yet') }}</flux:heading>
            <flux:text class="text-grey-inverse mb-4">{{ __('Create your first wallet to start tracking balances') }}</flux:text>
            <flux:modal.trigger name="create-wallet">
                <flux:button variant="primary" icon="plus" as="button">{{ __('Create wallet') }}</flux:button>
            </flux:modal.trigger>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($wallets as $wallet)
                <div class="rounded-xl bg-custom-ultra border border-custom-accent p-4 hover:border-zinc-300 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold truncate max-w-[220px]">{{ $wallet->name }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-custom-accent text-grey-inverse">{{ $wallet->unit }}</span>
                            </div>
                            <div class="text-xs text-grey-inverse mt-1">{{ __('Linked category') }}: {{ $wallet->category?->name }}</div>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:modal.trigger name="edit-wallet-{{ $wallet->id }}">
                                <flux:button size="sm" variant="ghost" icon="pencil" as="button" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}" />
                            </flux:modal.trigger>
                            <flux:modal.trigger name="delete-wallet-{{ $wallet->id }}">
                                <flux:button size="sm" variant="danger" icon="trash" as="button" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}" />
                            </flux:modal.trigger>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="text-xs text-grey-inverse">{{ __('Balance') }}</div>
                        <div class="text-xl font-bold">{{ rtrim(rtrim($wallet->balance, '0'), '.') }} {{ $wallet->unit }}</div>
                    </div>
                </div>

                <flux:modal name="edit-wallet-{{ $wallet->id }}" class="w-5/6">
                    <livewire:money.wallet-form :wallet="$wallet" />
                </flux:modal>

                <flux:modal name="delete-wallet-{{ $wallet->id }}" class="w-5/6">
                    <div class="space-y-6">
                        <flux:heading size="xl">{{ __('Delete wallet?') }}</flux:heading>
                        <flux:text>{{ __('This action cannot be undone.') }}</flux:text>
                        <div class="flex justify-end gap-2">
                            <flux:button variant="ghost" @click="Flux.modals().close('delete-wallet-{{ $wallet->id }}')">{{ __('Cancel') }}</flux:button>
                            <flux:button variant="danger" wire:click="delete('{{ $wallet->id }}')">{{ __('Delete') }}</flux:button>
                        </div>
                    </div>
                </flux:modal>
            @endforeach
        </div>
    @endif
</div>
