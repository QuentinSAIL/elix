<?php

namespace App\Livewire\Routine;

use App\Models\Routine;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Index extends Component
{
    public $user;

    public $routines;

    public $selectedRoutine = null;

    public function mount()
    {
        $this->selectedRoutine = Routine::first();
        $this->user = Auth::user();
        $this->routines = $this->user->routines()->with('frequency')->get();
    }

    public function selectRoutine($routineId)
    {
        if (! $routineId) {
            $this->selectedRoutine = null;
        } else {
            $routine = Routine::findOrFail($routineId);
            $this->selectedRoutine = $routine;
        }
    }

    public function delete(string $id)
    {
        if ($r = Routine::find($id)) {
            $r->delete();
            Toaster::success(__('Routine deleted successfully.'));
            $this->routines = $this->routines->filter(fn ($n) => $n->id !== $id);
        }
        $this->selectedRoutine = null;
    }

    #[On('routine-saved')]
    public function reRenderRoutines($routine)
    {
        $routine = Routine::find($routine['id']) ?? new Routine($routine);
        if (! $this->routines->contains('id', $routine['id'])) {
            $this->routines->prepend($routine);
        } else {
            $this->routines = $this->routines->map(function ($existingRoutine) use ($routine) {
                return $existingRoutine->id === $routine['id'] ? $routine : $existingRoutine;
            });
        }
    }

    public function render()
    {
        return view('livewire.routine.index');
    }
}
