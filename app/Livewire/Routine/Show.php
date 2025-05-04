<?php

namespace App\Livewire\Routine;

use App\Models\Routine;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;

class Show extends Component
{
    public $user;
    public $routine;
    public $currentTask = null; // null = pas encore démarré
    public $currentTaskIndex = null; // null = pas encore démarré

    public $isPaused = false;

    protected $listeners = [
        'timer-finished' => 'onTimerFinished',
    ];

    public function mount()
    {
        $this->user = Auth::user();
    }

    public function start()
    {
        $this->currentTaskIndex = -1;
        $this->next();
    }

    public function stop()
    {
        $this->currentTaskIndex = null;
        $this->currentTask = null;
        $this->dispatch('stop-timer');
        Toaster::success('Routine stopped !');
    }

    public function playPause()
    {
        $this->isPaused = !$this->isPaused;
        $this->dispatch('play-pause', ['isPaused' => $this->isPaused]);
        Toaster::success('' . ($this->isPaused ? 'Paused' : 'Play') . ' !');
    }

    public function updateCurrentTask($index)
    {
        $this->currentTask = $this->routine->tasks[$index] ?? null;
    }

    public function next()
    {
        $this->currentTaskIndex++;
        $this->updateCurrentTask($this->currentTaskIndex);
        if ($this->currentTask) {
            $this->startTimerForCurrentTask();
        }
    }

    public function onTimerFinished()
    {
        if ($this->currentTask && $this->currentTask->autoskip) {
            $this->next();
        }
    }

    private function startTimerForCurrentTask()
    {
        if (!$this->currentTask) {
            return;
        }

        $this->dispatch('start-timer', ['duration' => $this->currentTask->duration, 'currentIndex' => $this->currentTaskIndex]);
    }

    public function getCurrentTaskProperty()
    {
        return $this->routine->tasks[$this->currentTaskIndex] ?? null;
    }

    public function render()
    {
        return view('livewire.routine.show');
    }
}
