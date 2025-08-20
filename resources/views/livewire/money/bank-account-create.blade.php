<div>
    <flux:modal.trigger name="create-bank-account" id="create-bank-account">
        <div class="bg-custom-accent shadow-md rounded-lg p-4 w-64 h-full cursor-pointer hover flex items-center justify-center text-center" role="button" tabindex="0" aria-label="{{ __('Add new bank account') }}">
            <span class="m-1">
                {{ __('Add new bank account') }}
            </span>
            <flux:icon.plus class="text-2xl" aria-hidden="true" />
        </div>
    </flux:modal.trigger>

    <flux:modal name="create-bank-account" class="w-4/5 sm:w-2/3">
        <div class="space-y-6">
            <div>
                <flux:heading size="2xl">{{ __('Add a bank account') }}</flux:heading>
            </div>

            <div>
                <div class="mx-auto">
                    <input type="text" wire:model.live.debounce.300ms="searchTerm"
                        placeholder="{{ __('Search for a bank') }}"
                        class="w-full px-4 py-2 mb-4 border rounded outline-none mx-auto" aria-label="{{ __('Search for a bank') }}" />

                    {{-- conteneur des résultats --}}
                    <div class="bg-custom rounded-lg shadow max-h-64 overflow-y-auto">
                        @forelse($this->filteredBanks as $bank)
                            <button type="button" wire:click="updateSelectedBank('{{ $bank['id'] }}')"
                                class="w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-100 transition">
                                <img src="{{ $bank['logo'] }}" alt="{{ $bank['name'] }} logo"
                                    class="w-8 h-8 object-contain" />
                                <span class="flex-1 text-left">{{ $bank['name'] }}</span>
                            </button>
                        @empty
                            <div class="px-4 py-2">Aucune banque trouvée.</div>
                        @endforelse
                    </div>
                </div>


                @if ($selectedBank)
                    <div class="mt-4">
                        <div class="text-sm text-gray-200 space-y-2">
                            <flux:text>
                                <p>{{ __('By clicking validate, I confirm that I agree to provide my bank transactions for the last ') . $transactionTotalDays . __(' days.') }}
                                </p>
                                <p>{{ __('By clicking validate, I confirm that I agree to provide my bank transactions for a period of ') . $maxAccessValidForDays . __(' days.') }}
                                </p>
                                <p>{{ __('I understand that I can revoke this authorization at any time.') }}</p>
                            </flux:text>
                        </div>

                        <div class="flex gap-2 mt-6 justify-end pt-4">
                            <flux:modal.close>
                                <flux:button variant="ghost" class="px-4">
                                    {{ __('Cancel') }}
                                </flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" wire:click="addNewBankAccount">
                                {{ __('Validate') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
