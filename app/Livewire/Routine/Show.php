<?php

namespace App\Livewire\Routine;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Routine;

class Show extends Component
{
    public $user;
    public $routine;
    public $currentTask = null; // null = pas encore démarré
    public $currentTaskIndex = null; // null = pas encore démarré

    protected $listeners = [
        'timerFinished' => 'onTimerFinished',
    ];

    public function mount()
    {
        $this->user    = Auth::user();
    }

    public function start()
    {
        $this->currentTaskIndex = -1;
        $this->next();
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

    /**
     * Appelé depuis JS quand le timer arrive à 0
     */
    public function onTimerFinished()
    {
        // Si autoskip = true, on passe automatiquement à la suivante
        if ($this->currentTask && $this->currentTask->autoskip) {
            $this->next();
        }
        // sinon on reste sur la tâche et l'utilisateur doit cliquer « Fait »
    }

    /**
     * Émet l’événement JS pour démarrer le timer de la tâche courante
     */
    private function startTimerForCurrentTask()
    {
        if (! $this->currentTask) {
            return;
        }

        $this->dispatch('start-timer', ['duration' => $this->currentTask->duration, 'taskName' => $this->currentTask->name]);
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
