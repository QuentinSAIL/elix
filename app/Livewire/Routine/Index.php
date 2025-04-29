<?php

namespace App\Livewire\Routine;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Routine;

class Index extends Component
{
    public $routines;

    public function mount()
    {
        $this->refresh();
    }

    public function refresh()
    {
        $user = Auth::user();
        $this->routines = $user->routines;
    }

    public function delete($id)
    {
        $routine = Routine::find($id);
        if ($routine) {
            $routine->delete();
            session()->flash('message', 'Routine deleted successfully.');
        } else {
            session()->flash('message', 'Routine not found.');
        }
        $this->refresh();
    }

    public function render()
    {
        return view('livewire.routine.index');
    }
}
