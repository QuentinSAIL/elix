<?php

namespace Tests\Feature\Livewire\RoutineTask;

use App\Models\Routine;
use App\Models\RoutineTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormTest extends TestCase
{
    use RefreshDatabase;

    public function test_routine_task_form_component_can_be_rendered()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->assertStatus(200);
    }

    public function test_can_mount_with_new_task()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine]);

        $this->assertFalse($component->get('edition'));
        $this->assertEquals('create', $component->get('taskId'));
    }

    public function test_can_mount_with_existing_task()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);
        $task = RoutineTask::factory()->create([
            'routine_id' => $routine->id,
            'name' => 'Test Task',
            'duration' => 120,
        ]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine, 'task' => $task]);

        $this->assertTrue($component->get('edition'));
        $this->assertEquals($task->id, $component->get('taskId'));
    }

    public function test_can_reset_form()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine]);

        $component->call('resetForm');

        $this->assertEquals([
            'name' => '',
            'description' => '',
            'duration' => 60,
            'order' => 1,
            'autoskip' => true,
            'is_active' => true,
        ], $component->get('taskForm'));
    }

    public function test_can_handle_mobile_mode()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine, 'mobile' => true]);

        $this->assertTrue($component->get('mobile'));
        $this->assertStringContainsString('-m', $component->get('taskId'));
    }

    public function test_can_create_new_task()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', 'New Task')
            ->set('taskForm.description', 'Task Description')
            ->set('duration', 90)
            ->set('taskForm.order', 999)
            ->set('taskForm.autoskip', false)
            ->set('taskForm.is_active', true)
            ->call('save');

        // Verify a new task was created with the correct data
        $this->assertDatabaseHas('routine_tasks', [
            'routine_id' => $routine->id,
            'name' => 'New Task',
            'description' => 'Task Description',
            'duration' => 90,
            'order' => 999,
            'autoskip' => false,
            'is_active' => true,
        ]);
    }

    public function test_can_update_existing_task()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);
        $task = RoutineTask::factory()->create([
            'routine_id' => $routine->id,
            'name' => 'Original Task',
            'description' => 'Original Description',
            'duration' => 60,
            'order' => 1,
            'autoskip' => true,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine, 'task' => $task])
            ->set('taskForm.name', 'Updated Task')
            ->set('taskForm.description', 'Updated Description')
            ->set('duration', 120)
            ->set('taskForm.order', 3)
            ->set('taskForm.autoskip', false)
            ->set('taskForm.is_active', false)
            ->call('save');

        $task->refresh();
        $this->assertEquals('Updated Task', $task->name);
        $this->assertEquals('Updated Description', $task->description);
        $this->assertEquals(120, $task->duration);
        $this->assertEquals(3, $task->order);
        $this->assertFalse($task->autoskip);
        $this->assertFalse($task->is_active);
    }

    public function test_validates_required_fields()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', '')
            ->set('taskForm.duration', 0)
            ->set('taskForm.order', 0)
            ->call('save')
            ->assertHasErrors([
                'taskForm.name' => 'required',
            ]);
    }

    public function test_validates_string_max_length()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', str_repeat('a', 256))
            ->set('taskForm.description', str_repeat('a', 256))
            ->call('save')
            ->assertHasErrors([
                'taskForm.name' => 'max',
                'taskForm.description' => 'max',
            ]);
    }

    public function test_validates_duration_is_integer()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', 'Test Task')
            ->set('duration', 'not-a-number')
            ->call('save')
            ->assertHasErrors(['taskForm.duration' => 'integer']);
    }

    public function test_validates_order_is_integer()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', 'Test Task')
            ->set('taskForm.order', 'not-a-number')
            ->call('save')
            ->assertHasErrors(['taskForm.order' => 'integer']);
    }

    public function test_validates_boolean_fields()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', 'Test Task')
            ->set('taskForm.autoskip', 'not-boolean')
            ->set('taskForm.is_active', 'not-boolean')
            ->call('save')
            ->assertHasErrors([
                'taskForm.autoskip' => 'boolean',
                'taskForm.is_active' => 'boolean',
            ]);
    }

    public function test_populate_form_sets_duration_from_task_form()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);
        $task = RoutineTask::factory()->create([
            'routine_id' => $routine->id,
            'duration' => 180,
        ]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine, 'task' => $task]);

        $this->assertEquals(180, $component->get('duration'));
    }

    public function test_populate_form_sets_default_duration_for_new_task()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine]);

        $this->assertEquals(60, $component->get('duration'));
    }

    public function test_save_updates_duration_from_component_property()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', 'Test Task')
            ->set('duration', 150)
            ->call('save');

        $this->assertDatabaseHas('routine_tasks', [
            'routine_id' => $routine->id,
            'name' => 'Test Task',
            'duration' => 150,
        ]);
    }

    public function test_mobile_mode_affects_task_id_for_new_task()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine, 'mobile' => true]);

        $this->assertEquals('create-m', $component->get('taskId'));
    }

    public function test_mobile_mode_affects_task_id_for_existing_task()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);
        $task = RoutineTask::factory()->create(['routine_id' => $routine->id]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine, 'task' => $task, 'mobile' => true]);

        $this->assertEquals($task->id.'-m', $component->get('taskId'));
    }

    public function test_reset_form_after_save()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', 'Test Task')
            ->call('save');

        $this->assertEquals([
            'name' => '',
            'description' => '',
            'duration' => 60,
            'order' => 1,
            'autoskip' => true,
            'is_active' => true,
        ], $component->get('taskForm'));
    }

    public function test_can_handle_task_with_null_description()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);
        $task = RoutineTask::factory()->create([
            'routine_id' => $routine->id,
            'description' => null,
        ]);

        $component = Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine, 'task' => $task]);

        $this->assertNull($component->get('taskForm.description'));
    }

    public function test_can_handle_task_with_empty_description()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('routine-task.form', ['routine' => $routine])
            ->set('taskForm.name', 'Test Task')
            ->set('taskForm.description', '')
            ->call('save');

        $this->assertDatabaseHas('routine_tasks', [
            'routine_id' => $routine->id,
            'name' => 'Test Task',
            'description' => '',
        ]);
    }

    public function test_can_handle_task_with_all_boolean_combinations()
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->create(['user_id' => $user->id]);

        // Test all combinations of boolean values
        $combinations = [
            ['autoskip' => true, 'is_active' => true],
            ['autoskip' => true, 'is_active' => false],
            ['autoskip' => false, 'is_active' => true],
            ['autoskip' => false, 'is_active' => false],
        ];

        foreach ($combinations as $index => $combination) {
            $component = Livewire::actingAs($user)
                ->test('routine-task.form', ['routine' => $routine])
                ->set('taskForm.name', "Test Task {$index}")
                ->set('taskForm.autoskip', $combination['autoskip'])
                ->set('taskForm.is_active', $combination['is_active'])
                ->call('save');

            $this->assertDatabaseHas('routine_tasks', [
                'routine_id' => $routine->id,
                'name' => "Test Task {$index}",
                'autoskip' => $combination['autoskip'],
                'is_active' => $combination['is_active'],
            ]);
        }
    }
}
