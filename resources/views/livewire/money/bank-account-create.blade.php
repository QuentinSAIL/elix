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
                    class="flex-1 px-4 py-2 w-full border rounded-lg bg-custom outline-none">
                    <option value="">{{ __('Select a bank') }}</option>
                    @foreach ($banks as $bank)
                        <option value="{{ $bank['id'] }}">{{ $bank['name'] }}</option>
                    @endforeach
                </select>

                @if ($selectedBank)
                    <div class="mt-4">
                        <div class="text-sm text-gray-600 space-y-2">
                            <flux:text>
                                {{ __('By clicking validate, I confirm that I agree to provide my bank transactions for the last ') . $transactionTotalDays . __(' days.') }}
                            </flux:text>
                            <flux:text>
                                {{ __('By clicking validate, I confirm that I agree to provide my bank transactions for a period of ') . $maxAccessValidForDays . __(' days.') }}
                            </flux:text>
                            <flux:text>
                                {{ __('I understand that I can revoke this authorization at any time.') }}
                            </flux:text>
                        </div>
                        <flux:button class="mt-4" variant="primary" wire:click="acceptUserAgreement">
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
