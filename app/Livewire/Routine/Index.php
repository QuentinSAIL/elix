<?php

namespace App\Livewire\Routine;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public $routines;

    public function mount()
    {
        $user = Auth::user();
        $this->routines = $user->routines;
    }

    public function render()
    {
        return view('livewire.routine.index');
    }
}
