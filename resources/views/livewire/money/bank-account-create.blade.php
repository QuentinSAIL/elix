<div>
    <flux:modal.trigger name="create-bank-account" id="create-bank-account">
        <div class="bg-custom shadow-md rounded-lg p-4 w-full h-full cursor-pointer">
            <span class="m-1">
                {{ __('Add new bank account') }}
            </span>
            <flux:icon.plus class="text-2xl text-white" />
        </div>
    </flux:modal.trigger>

    <flux:modal name="create-bank-account" class="w-5/6">
        <div class="space-y-6">
            <div>
                <flux:heading size="2xl">{{ __('Add a bank account') }}</flux:heading>
            </div>

            <div>
<div class="w-full max-w-md mx-auto">
  {{-- champ de recherche --}}
  <input
    type="text"
    wire:model.live.debounce.300ms="searchTerm"
    placeholder="Rechercher une banque…"
    class="w-full px-4 py-2 mb-4 border rounded outline-none"
  />

  {{-- conteneur des résultats --}}
  <div class="bg-custom rounded-lg shadow max-h-64 overflow-y-auto">
    @forelse($this->getFilteredBanksProperty() as $bank)
      <button
        type="button"
        wire:click="updateSelectedBank('{{ $bank['id'] }}')"
        class="w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-100 transition"
      >
        <img
          src="{{ $bank['logo'] }}"
          alt="{{ $bank['name'] }} logo"
          class="w-8 h-8 object-contain"
        />
        <span class="flex-1 text-left">{{ $bank['name'] }}</span>
      </button>
    @empty
      <div class="px-4 py-2 text-gray-500">Aucune banque trouvée.</div>
    @endforelse
  </div>
</div>


                @if ($selectedBank)
                    <div class="mt-4">
                        <div class="text-sm text-gray-600 space-y-2">
                            <flux:text>
                                <p>{{ __('By clicking validate, I confirm that I agree to provide my bank transactions for the last ') . $transactionTotalDays . __(' days.') }}</p>
                                <p>{{ __('By clicking validate, I confirm that I agree to provide my bank transactions for a period of ') . $maxAccessValidForDays . __(' days.') }}</p>
                                <p>{{ __('I understand that I can revoke this authorization at any time.') }}</p>
                            </flux:text>
                        </div>
                        <flux:button class="mt-4" variant="primary" wire:click="addNewBankAccount">
                            {{ __('Validate') }}
                        </flux:button>
                    </div>
                @endif
            </div>


            <div class="flex mt-6 justify-between">
            </div>
        </div>
    </flux:modal>
</div>
