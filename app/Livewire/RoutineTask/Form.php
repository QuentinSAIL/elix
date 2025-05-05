<?php

namespace App\Livewire\RoutineTask;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Form extends Component
{
    public $user;

    public $routine;

    public $edition;

    public $taskId;

    public $task; // c'est rempli quand on est en edition

    public $taskForm = [
        'name' => '',
        'description' => '',
        'duration' => 60,
        'order' => 0,
        'autoskip' => true,
        'is_active' => true,
    ];

    #[On('task-updated')]
    public function mount()
    {
        $this->user = Auth::user();
        $this->populateForm();
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
            // set the default order to the last task + 1
            $order = $this->routine->tasks()->max('order');
            $this->taskForm['order'] = $order ? $order + 1 : 1;
        }
    }

    public function save()
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
            Toaster::error(__('Task content is invalid.'));
            return;
        }

        if ($this->edition) {
            $this->task->update($this->taskForm);
        } else {
            $this->routine->tasks()->create($this->taskForm);
        }

        Flux::modals()->close('task-form-' . $this->taskId);
        $this->dispatch('task-saved');
    }

    public function render()
    {
        return view('livewire.routine-task.form');
    }
}
