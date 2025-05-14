<?php

namespace App\Livewire\Money;

use Livewire\Component;

class Dashboard extends Component
{

    public $dateRange = 'today';
    public $customStartDate;
    public $customEndDate;

    public function mount()
    {

    }

    public function render()
    {
        return view('livewire.money.dashboard');
    }
}
