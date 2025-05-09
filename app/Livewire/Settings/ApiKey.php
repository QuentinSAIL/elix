<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ApiKey extends Component
{
    public $user;
    public $keys;

    public function mount()
    {
        $this->user = Auth::user();
        $this->keys = $this->user->apiKeys()->get();
    }

    public function updateApiKeys()
    {
        //
    }

    public function render()
    {
        return view('livewire.settings.api-keys');
    }
}
