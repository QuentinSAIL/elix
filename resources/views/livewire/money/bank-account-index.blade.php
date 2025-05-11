<div>
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-52">
        @foreach ($accounts as $account)
            <div class="bg-custom shadow-md rounded-lg p-4 w-1/3">
                <div class="flex items-center justify-between">
                    <input type="text" class="text-lg font-bold border-none focus:ring-0 focus:outline-none stroke-0"
                        value="{{ $account->name }}"
                        wire:change="updateAccountName('{{ $account->id }}', $event.target.value)" />
                    <img src="{{ $account->logo }}" alt="Logo" class="h-12 w-12 rounded">
                </div>
                <flux:text>
                    {{ __('Balance: ') }}{{ $account->balance }}
                    @if ($account->currency === 'EUR')
                        €
                    @endif
                </flux:text>
                <flux:text>
                    <span class="block text-sm text-gray-500">
                        {{ chunk_split($account->iban, 4, ' ') }}
                    </span>
                    <span class="block text-sm text-gray-500">
                        {{ $account->owner_name }}
                    </span>
                </flux:text>
                <div class="flex justify-end">
                    <flux:modal.trigger name="delete-account-{{ $account->id }}">
                        <flux:button variant="danger">
                            {{ __('Delete') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>
                <flux:modal name="delete-account-{{ $account->id }}">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Delete bank account?') }}</flux:heading>

                            <flux:text class="mt-2">
                                <p>{{ __('You\'re about to delete this bank account.') }}</p>
                                <p>{{ __('This action cannot be reversed.') }}</p>
                            </flux:text>
                        </div>

                        <div class="flex gap-2">
                            <flux:spacer />

                            <flux:modal.close>
                                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                            </flux:modal.close>

                            <flux:button wire:click="delete('{{ $account->id }}')" variant="danger">
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </div>
                </flux:modal>
            </div>
        @endforeach
        <livewire:money.bank-account-create wire:key="create-bank-account" />
    </div>
</div>
