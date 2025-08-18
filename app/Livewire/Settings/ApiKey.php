<?php

namespace App\Livewire\Settings;

use App\Models\ApiService;
use App\Services\GoCardlessDataService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ApiKey extends Component
{
    public $user;

    public $services;

    public $secret_ids = [];

    public $secret_keys = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->services = ApiService::all();
        $existing = $this->user->apiKeys()->get()->keyBy('api_service_id');

        foreach ($this->services as $service) {
            /** @var \App\Models\ApiKey|null $key */
            $key = $existing->get($service->id);
            $this->secret_ids[$service->id] = $key ? $key->secret_id : '';
            $this->secret_keys[$service->id] = $key ? $key->secret_key : '';
        }
    }

    public function updateApiKeys()
    {
        foreach ($this->services as $service) {
            $secretId = trim($this->secret_ids[$service->id]);
            $secretKey = trim($this->secret_keys[$service->id]);

            $oldCredentials = $this->user->apiKeys()->where('api_service_id', $service->id)->first();

            if ($secretId !== '' || $secretKey !== '') {
                $this->user->apiKeys()->updateOrCreate(
                    ['api_service_id' => $service->id],
                    ['secret_id' => $secretId, 'secret_key' => $secretKey]
                );
            }
            if ($service->name === 'GoCardless' && ! $this->testGoCardless()) {
                if ($oldCredentials) {
                    $this->user->apiKeys()->updateOrCreate(
                        ['api_service_id' => $service->id],
                        ['secret_id' => $oldCredentials->secret_id, 'secret_key' => $oldCredentials->secret_key]
                    );
                }
                $this->dispatch('show-toast', type: 'error', message: __('Failed to validate GoCardless credentials. Changes have not been saved.'));

                return;
            }
        }
        foreach ($this->services as $service) {
            $this->secret_keys[$service->id] = '';
            $this->secret_ids[$service->id] = '';
        }

        $this->dispatch('show-toast', type: 'success', message: __('API Keys updated successfully!'));
    }

    public function testGoCardless()
    {
        $goCardlessDataService = app(GoCardlessDataService::class);

        return $goCardlessDataService->accessToken(false) ? true : false;
    }

    public function deleteApiKeys($serviceId)
    {
        $this->user->apiKeys()->where('api_service_id', $serviceId)->delete();
        $this->dispatch('show-toast', type: 'success', message: __('API Key deleted successfully!'));
    }

    public function render()
    {
        return view('livewire.settings.api-key');
    }
}
