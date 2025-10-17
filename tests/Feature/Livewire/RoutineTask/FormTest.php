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
}
