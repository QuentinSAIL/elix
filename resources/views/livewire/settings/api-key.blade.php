<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('API Keys')" :subheading=" __('Update your API keys')">

        <div class="space-y-6">
            @foreach($services as $service)
                <div>
                    <div class="flex items-center">
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ $service->name }}</p>
                            <p class="text-xs text-gray-500">{{ $service->description }}</p>
                        </div>
                        @if ($this->user->hasApiKey($service->id))
                            <div class="relative group ml-2">
                                <flux:icon.check class="h-5 w-5 text-green-500 mr-2" aria-hidden="true" />
                                <div class="absolute hidden group-hover:block bg-custom text-custom-inverse text-xs rounded py-1 px-2 -mt-8 ml-6 w-64">
                                    {{ __('You have already provided the API keys for this service') }}
                                </div>
                            </div>

                            <flux:icon.trash
                                class="text-red-500 cursor-pointer"
                                variant="micro"
                                wire:click="deleteApiKeys('{{ $service->id }}')"
                                title="{{ __('Delete API Keys') }}"
                                role="button"
                                tabindex="0"
                                aria-label="{{ __('Delete API Keys for ') }} {{ $service->name }}"
                            />
                        @endif
                    </div>

                    <div class="my-2">
                        <flux:input
                            wire:model.defer="secret_ids.{{ $service->id }}"
                            type="text"
                            placeholder="{{ __('Secret ID') }}"
                            class="pl-10"
                            aria-label="{{ __('Secret ID for ') }} {{ $service->name }}"
                        />
                    </div>

                    <div class="">
                        <flux:input
                            wire:model.defer="secret_keys.{{ $service->id }}"
                            type="text"
                            placeholder="{{ __('Secret Key') }}"
                            class="pl-10"
                            aria-label="{{ __('Secret Key for ') }} {{ $service->name }}"
                        />
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="updateApiKeys">
                    {{ __('Save') }}
                </flux:button>
            </div>

    </x-settings.layout>
</section>
