<?php

namespace App\Livewire\RoutineTask;

use App\Http\Livewire\Traits\Notifies;
use App\Services\RoutineService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Form extends Component
{
    use Notifies;
    public $user;

    public $routine;

    public $edition;

    public $taskId;

    public $task; // c'est rempli quand on est en edition

    public $taskForm;

    #[On('task-updated')]
    public function mount()
    {
        $this->user = Auth::user();
        $this->populateForm();
    }

    public function resetForm()
    {
        $this->taskForm = [
            'name' => '',
            'description' => '',
            'duration' => 60,
            'order' => 0,
            'autoskip' => true,
            'is_active' => true,
        ];

        // set the default order to the last task + 1
        $order = $this->routine->tasks()->whereNull('deleted_at')->max('order');
        $this->taskForm['order'] = $order ? $order + 1 : 1;
    }

    public function populateForm()
    {
        if ($this->task) {
            $this->taskId = $this->task->id;
            $this->edition = true;
            $this->taskForm = [
                'name' => $this->task->name,
                'description' => $this->task->description,
                'duration' => $this->task->duration,
                'order' => $this->task->order,
                'autoskip' => $this->task->autoskip,
                'is_active' => $this->task->is_active,
            ];
        } else {
            $this->taskId = 'create';
            $this->edition = false;
            $this->resetForm();
        }
    }

    public function save(RoutineService $routineService)
    {
        $rules = [
            'taskForm.name' => 'required|string|max:255',
            'taskForm.description' => 'nullable|string|max:255',
            'taskForm.duration' => 'required|integer|min:1',
            'taskForm.order' => 'required|integer|min:1',
            'taskForm.autoskip' => 'boolean',
            'taskForm.is_active' => 'boolean',
        ];

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            $this->notifyError(__('Task content is invalid.'));

            return;
        }

        $routineService->saveTask(
            $this->taskForm,
            $this->routine,
            $this->task
        );

        Flux::modals()->close('task-form-'.$this->taskId);
        $this->dispatch('task-saved');
    }

    public function render()
    {
        return view('livewire.routine-task.form');
    }
}
