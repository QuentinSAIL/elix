<?php

namespace App\Livewire\Routine;

use App\Models\Routine;
use Livewire\Component;
use App\Models\RoutineTask;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Show extends Component
{
    public $user;
    public $routine;
    public $currentTask = null; // null = pas encore démarré
    public $currentTaskIndex = null; // null = pas encore démarré
    public $isFinished = false;

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
        Toaster::info(__('Routine started.'));
    }

    public function stop()
    {
        $this->currentTaskIndex = null;
        $this->currentTask = null;
        $this->dispatch('stop-timer');
        Toaster::info(__('Routine stopped.'));
    }

    public function playPause()
    {
        $this->isPaused = !$this->isPaused;
        $this->dispatch('play-pause', ['isPaused' => $this->isPaused]);
        Toaster::info(($this->isPaused ? __('Pause') : __('Resume')) . ' !');
    }

    public function updateCurrentTask($index)
    {
        $this->currentTask = $this->routine->tasks[$index] ?? null;
        if (!$this->currentTask) {
            $this->isFinished = true;
            $this->stop();
            Toaster::success(__('Routine finished.'));
        }
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

    public function updateTaskOrder(array $orderedIds)
    {
        foreach ($orderedIds as $i => $id) {
            RoutineTask::where('id', $id)->update(['order' => $i + 1]);
        }
        $this->routine->refresh();
        $this->dispatch('task-updated');
        Toaster::success(__('Task order updated.'));
    }

    public function deleteTask(RoutineTask $task)
    {
        DB::transaction(function () use ($task) {
            $order = $task->order;
            $task->delete();

            // Update the order of subsequent tasks
            RoutineTask::where('routine_id', $this->routine->id)->where('order', '>', $order)->decrement('order');

            $this->routine->refresh();
        });
        Toaster::success(__('Task deleted successfully.'));
    }

    public function duplicateTask(RoutineTask $task)
    {
        DB::transaction(function () use ($task) {
            // Increment the order of subsequent tasks
            RoutineTask::where('routine_id', $this->routine->id)->where('order', '>', $task->order)->increment('order');

            // Create the duplicated task with the updated order
            $newTask = $task->replicate();
            $newTask->order = $task->order + 1;
            $newTask->routine_id = $this->routine->id;
            $newTask->save();

            $this->routine->refresh();
        });
        Toaster::success(__('Task duplicated successfully.'));
    }

    #[On('task-saved')]
    public function onTaskSaved()
    {
        $this->routine->refresh();
    }

    public function render()
    {
        return view('livewire.routine.show');
    }
}
