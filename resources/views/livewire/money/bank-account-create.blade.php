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
                <select wire:change="updateSelectedBank($event.target.value)"
                    class="flex-1 px-4 py-2 border rounded-lg bg-custom">
                    <option value="">{{ __('Select a bank') }}</option>
                    @foreach ($banks as $bank)
                        <option value="{{ $bank['id'] }}">{{ $bank['name'] }}</option>
                    @endforeach
                </select>

                @if ($selectedBank)
                    <div class="mt-4">
                        <p>
                            {{ __('En cliquant sur valider, je confirme accepter de fournir mes transactions bancaire des ') . $transactionTotalDays . __(' derniers jours') }}
                            <br>
                            {{ __('En cliquant sur valider, je confirme accepter de fournir mes transactions bancaire pendant ') . $maxAccessValidForDays . __(' jours') }}
                            <br>
                            {{ __('Je comprends que je peux annuler cette autorisation à tout moment') }}
                        </p>
                        <flux:button class="mt-4" wire:click="acceptUserAgreement"> {{ __('Validate') }}
                        </flux:button>
                    </div>
                @endif
            </div>


            <div class="flex mt-6 justify-between">
            </div>
        </div>
    </flux:modal>
</div>
