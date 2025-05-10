<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\ApiService;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;

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
        $existing = $this->user->apiKeys()->get()->keyBy('service');

        foreach ($this->services as $service) {
            $key = $existing->get($service->id);
            $this->secret_ids[$service->id]  = $key ? $key->secret_id  : '';
            $this->secret_keys[$service->id] = $key ? $key->secret_key : '';
        }
    }

    public function updateApiKeys()
    {
        foreach ($this->services as $service) {
            $secretId  = trim($this->secret_ids[$service->id]);
            $secretKey = trim($this->secret_keys[$service->id]);
            if ($secretId !== '' || $secretKey !== '') {
                $this->user->apiKeys()->updateOrCreate(
                    ['api_service_id' => $service->id],
                    ['secret_id' => $secretId, 'secret_key' => $secretKey]
                );
            }
        }

        Toaster::success(__('API Keys updated successfully!'));
    }

    public function render()
    {
        return view('livewire.settings.api-key');
    }
}
