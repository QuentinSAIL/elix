<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('API Keys')" :subheading=" __('Update your API keys')">

        <div class="space-y-6">
            @foreach($services as $service)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div class="col-span-1 flex items-center">
                        @if ($this->user->hasApiKey($service->id))
                            <div class="relative group">
                                <flux:icon.check class="h-5 w-5 text-green-500 mr-2" />
                                <div class="absolute hidden group-hover:block bg-custom text-custom-inverse text-xs rounded py-1 px-2 -mt-8 ml-6 w-64">
                                    {{ __('You have already provided the API keys for this service') }}
                                </div>
                            </div>
                        @endif
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ $service->name }}</p>
                            <p class="text-xs text-gray-500">{{ $service->description }}</p>
                        </div>
                    </div>

                    <div class="col-span-1 relative">
                        @if(!empty($secret_ids[$service->id]))
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                {{-- Icône check vert --}}
                                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        @endif
                        <input
                            wire:model.defer="secret_ids.{{ $service->id }}"
                            type="text"
                            placeholder="{{ __('Secret ID') }}"
                            class="block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <div class="col-span-1 relative">
                        @if(!empty($secret_keys[$service->id]))
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                {{-- Icône check vert --}}
                                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        @endif
                        <input
                            wire:model.defer="secret_keys.{{ $service->id }}"
                            type="text"
                            placeholder="{{ __('Secret Key') }}"
                            class="block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                </div>
            @endforeach

            <div class="pt-4">
                <flux:button variant="primary" wire:click="updateApiKeys"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    {{ __('Save API Keys') }}
                </flux:button>
            </div>

    </x-settings.layout>
</section>
