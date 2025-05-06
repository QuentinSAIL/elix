<?php

namespace App\Livewire\Money;

use Livewire\Component;
use App\Services\GoCardlessDataService;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public $accounts;
    public $user;

    public function mount()
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
    }

    public function render()
    {
        return view('livewire.money.index');
    }
}
