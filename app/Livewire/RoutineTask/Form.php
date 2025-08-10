<?php

namespace App\Livewire\RoutineTask;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
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
            'order' => 1,
            'autoskip' => true,
            'is_active' => true,
        ];
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

        $this->validate($rules);

        if ($this->edition) {
            $this->task->name = $this->taskForm['name'];
            $this->task->description = $this->taskForm['description'];
            $this->task->duration = $this->taskForm['duration'];
            $this->task->order = $this->taskForm['order'];
            $this->task->autoskip = $this->taskForm['autoskip'];
            $this->task->is_active = $this->taskForm['is_active'];
            $this->task->save();
            $this->task->refresh();
        } else {
            $this->routine->tasks()->create($this->taskForm);
        }

        Flux::modals()->close('task-form-'.$this->taskId);
        $this->dispatch('task-saved');
    }

    public function render()
    {
        return view('livewire.routine-task.form');
    }
}
