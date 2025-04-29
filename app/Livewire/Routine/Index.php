<?php

namespace App\Livewire\Routine;

use App\Models\Routine;
use Livewire\Component;
use App\Models\Frequency;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public $user;
    public $frequencies;
    public $routines;

    public $newRoutine = [
        'name'            => '',
        'description'     => '',
        'start_datetime'  => '',
        'end_datetime'    => '',
        'frequency_id'    => '',
        'is_active'       => true,
    ];

    public function mount()
    {
        $this->user = Auth::user();
        $this->frequencies = Frequency::all();
        $this->refresh();
    }

    public function refresh()
    {
        $this->routines = $this->user->routines;
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
        Toaster::success("La routine $routine->name a bien été supprimé !");
        $this->refresh();
    }

    public function create()
    {
        $this->validate([
            'newRoutine.name'            => 'required|string|max:255',
            'newRoutine.start_datetime'  => 'required|date',
            'newRoutine.end_datetime'    => 'nullable|date|after_or_equal:start_datetime',
            'newRoutine.frequency_id'    => 'required|exists:frequencies,id',
        ]);

        if ($this->newRoutine["frequency_id"] == null) {
            $this->newRoutine["frequency_id"] = 1;
        }

        $routine = $this->user->routines()->create([
            'name'            => $this->newRoutine["name"],
            'description'     => $this->newRoutine["description"],
            'start_datetime'  => $this->newRoutine["start_datetime"],
            'end_datetime'    => $this->newRoutine["end_datetime"],
            'frequency_id'    => $this->newRoutine["frequency_id"],
            'is_active'       => $this->newRoutine["is_active"],
        ]);

        Toaster::success("La routine $routine->name a bien été crée !");
        $this->refresh();
    }

    public function render()
    {
        return view('livewire.routine.index');
    }
}
