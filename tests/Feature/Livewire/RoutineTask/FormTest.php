<?php

namespace Tests\Feature\Livewire\RoutineTask;

use App\Livewire\RoutineTask\Form;
use App\Models\Routine;
use App\Models\RoutineTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @covers \App\Livewire\RoutineTask\Form
 */
class FormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Routine $routine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->routine = Routine::factory()->create(['user_id' => $this->user->id]);
    }

    #[test]
    public function routine_task_form_component_can_be_rendered()
    {
        Livewire::test(Form::class, ['routine' => $this->routine])
            ->assertStatus(200);
    }

    #[test]
    public function it_populates_form_for_new_task()
    {
        Livewire::test(Form::class, ['routine' => $this->routine])
            ->assertSet('edition', false)
            ->assertSet('taskForm.name', '')
            ->assertSet('taskForm.duration', 60)
            ->assertSet('taskForm.order', 1);
    }

    #[test]
    public function it_populates_form_for_existing_task()
    {
        $task = RoutineTask::factory()->for($this->routine)->create([
            'name' => 'Existing Task',
            'duration' => 120,
            'order' => 5,
            'autoskip' => false,
            'is_active' => false,
        ]);

        Livewire::test(Form::class, ['routine' => $this->routine, 'task' => $task])
            ->assertSet('edition', true)
            ->assertSet('taskForm.name', 'Existing Task')
            ->assertSet('taskForm.duration', 120)
            ->assertSet('taskForm.order', 5)
            ->assertSet('taskForm.autoskip', false)
            ->assertSet('taskForm.is_active', false);
    }

    #[test]
    public function it_resets_form()
    {
        Livewire::test(Form::class, ['routine' => $this->routine])
            ->set('taskForm.name', 'Test Name')
            ->set('taskForm.duration', 123)
            ->call('resetForm')
            ->assertSet('taskForm.name', '')
            ->assertSet('taskForm.duration', 60);
    }

    #[test]
    public function it_creates_new_routine_task()
    {
        Livewire::test(Form::class, ['routine' => $this->routine])
            ->set('taskForm.name', 'New Task')
            ->set('taskForm.description', 'Task Description')
            ->set('taskForm.duration', 90)
            ->set('taskForm.order', 2)
            ->set('taskForm.autoskip', true)
            ->set('taskForm.is_active', true)
            ->call('save')
            ->assertDispatched('task-saved')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('routine_tasks', [
            'routine_id' => $this->routine->id,
            'name' => 'New Task',
            'description' => 'Task Description',
            'duration' => 90,
            'order' => 2,
            'autoskip' => true,
            'is_active' => true,
        ]);
    }

    #[test]
    public function it_updates_existing_routine_task()
    {
        $task = RoutineTask::factory()->for($this->routine)->create([
            'name' => 'Old Name',
            'duration' => 60,
        ]);

        Livewire::test(Form::class, ['routine' => $this->routine, 'task' => $task])
            ->set('taskForm.name', 'Updated Name')
            ->set('taskForm.duration', 180)
            ->call('save')
            ->assertDispatched('task-saved')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('routine_tasks', [
            'id' => $task->id,
            'name' => 'Updated Name',
            'duration' => 180,
        ]);
    }

    #[test]
    public function it_validates_required_fields()
    {
        Livewire::test(Form::class, ['routine' => $this->routine])
            ->set('taskForm.name', '')
            ->set('taskForm.duration', '')
            ->set('taskForm.order', '')
            ->call('save')
            ->assertHasErrors([
                'taskForm.name' => 'required',
                'taskForm.duration' => 'required',
                'taskForm.order' => 'required',
            ]);
    }

    #[test]
    public function it_validates_duration_minimum()
    {
        Livewire::test(Form::class, ['routine' => $this->routine])
            ->set('taskForm.name', 'Test')
            ->set('taskForm.duration', 0)
            ->call('save')
            ->assertHasErrors([
                'taskForm.duration' => 'min',
            ]);
    }

    #[test]
    public function it_validates_order_minimum()
    {
        Livewire::test(Form::class, ['routine' => $this->routine])
            ->set('taskForm.name', 'Test')
            ->set('taskForm.order', 0)
            ->call('save')
            ->assertHasErrors([
                'taskForm.order' => 'min',
            ]);
    }
}