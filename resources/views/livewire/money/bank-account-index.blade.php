<div>
    <div class="flex flex-wrap gap-4 py-4 justify-center">
        @foreach ($accounts as $account)
            <!-- Carte -->
            <div class="bg-custom-accent shadow-md rounded-lg p-4 w-full justify-between">
                <!-- En-tête : nom + logo -->
                <div class="flex items-center justify-between mb-2">
                    <input
                        type="text"
                        class="text-lg font-bold input-none w-full mr-2 bg-transparent focus:outline-none"
                        value="{{ $account->name }}"
                        wire:change="updateAccountName('{{ $account->id }}', $event.target.value)"
                    />
                    <img
                        src="{{ $account->logo }}"
                        alt="{{ $account->name }} logo"
                        class="h-10 w-10 sm:h-12 sm:w-12 rounded object-contain"
                    >
                </div>

                <!-- Solde -->
                <flux:text class="mb-2">
                    {{ __('Balance: ') }}{{ $account->balance }}
                    @if ($account->currency === 'EUR') € @endif
                </flux:text>

                <!-- IBAN + titulaire -->
                <flux:text>
                    <span class="block text-sm text-grey break-words">
                        {{ chunk_split($account->iban, 4, ' ') }}
                    </span>
                    <span class="block text-sm text-grey">
                        {{ $account->owner_name }}
                    </span>
                </flux:text>

                <!-- Bouton Supprimer -->
                <div class="flex justify-end mt-4">
                    <flux:modal.trigger name="delete-account-{{ $account->id }}">
                        <flux:button variant="danger">{{ __('Delete') }}</flux:button>
                    </flux:modal.trigger>
                </div>

                <!-- Modal confirmation -->
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

        <!-- Formulaire d’ajout -->
        <livewire:money.bank-account-create wire:key="create-bank-account" class="w-2/5" />
    </div>
</div>
