<?php

namespace App\Livewire\Routine;

use Livewire\Component;

class Show extends Component
{
    public $user;
    public $routine;

    public $currentTaskIndex = 2;

    public function render()
    {
        return view('livewire.routine.show');
    }
}
