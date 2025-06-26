<?php

namespace App\Livewire\Settings;

use App\Http\Livewire\Traits\Notifies;
use App\Models\ApiService;
use App\Services\GoCardlessDataService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ApiKey extends Component
{
    use Notifies;
    public $user;

    public $services;

    public $secret_ids = [];

    public $secret_keys = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->services = ApiService::all();
        $existing = $this->user->apiKeys()->get()->keyBy('service');

        foreach ($this->services as $service) {
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
            if ($service->name === 'GoCardless' && ! $this->testGoCardless($goCardlessDataService)) {
                if ($oldCredentials) {
                    $this->user->apiKeys()->updateOrCreate(
                        ['api_service_id' => $service->id],
                        ['secret_id' => $oldCredentials->secret_id, 'secret_key' => $oldCredentials->secret_key]
                    );
                }
                $this->notifyError(__('Failed to validate GoCardless credentials. Changes have not been saved.'));

                return;
            }
        }
        $this->secret_keys[$service->id] = '';
        $this->secret_ids[$service->id] = '';

        $this->notifySuccess(__('API Keys updated successfully!'));
    }

    public function testGoCardless(GoCardlessDataService $goCardlessDataService)
    {
        return $goCardlessDataService->accessToken(false) ? true : false;
    }

    public function deleteApiKeys($serviceId)
    {
        $this->user->apiKeys()->where('api_service_id', $serviceId)->delete();
        $this->notifySuccess(__('API Key deleted successfully!'));
    }

    public function render()
    {
        return view('livewire.settings.api-key');
    }
}
