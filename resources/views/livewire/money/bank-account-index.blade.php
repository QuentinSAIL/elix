<div>
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-48">
        @foreach ($accounts as $account)
            <div class="bg-custom shadow-md rounded-lg p-4 w-1/4">
                <input type="text" class="text-lg font-bold border-none focus:ring-0 focus:outline-none stroke-0"
                    value="{{ $account->name }}"
                    wire:change="updateAccountName('{{ $account->id }}', $event.target.value)" />
                <p class="text-gray-600">Balance: {{ $account->balance }}</p>
                <div class="flex justify-end mt-9">
                    <flux:modal.trigger name="delete-account-{{ $account->id }}">
                        <flux:button variant="danger">
                            {{ __('Delete') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>
                <flux:modal name="delete-account-{{ $account->id }}">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Delete bank account?</flux:heading>

                            <flux:text class="mt-2">
                                <p>You're about to delete this bank account.</p>
                                <p>This action cannot be reversed.</p>
                            </flux:text>
                        </div>

                        <div class="flex gap-2">
                            <flux:spacer />

                            <flux:modal.close>
                                <flux:button variant="ghost">Cancel</flux:button>
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
