<?php

use App\Livewire\Routine\Show;
use App\Models\Routine;
use App\Models\RoutineTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('routine show component can be rendered', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create();

    Livewire::test(Show::class, ['routine' => $routine])
        ->assertStatus(200)
        ->assertSee($routine->name);
});

test('can start routine', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create();

    Livewire::test(Show::class, ['routine' => $routine])
        ->call('start')
        ->assertSet('currentTaskIndex', 0);
});

test('can stop routine', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create();

    Livewire::test(Show::class, ['routine' => $routine])
        ->set('currentTaskIndex', 0)
        ->set('currentTask', $task)
        ->call('stop')
        ->assertSet('currentTaskIndex', null)
        ->assertSet('currentTask', null)
        ->assertDispatched('stop-timer');
});

test('can play pause routine', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create();

    Livewire::test(Show::class, ['routine' => $routine])
        ->call('playPause')
        ->assertSet('isPaused', true)
        ->assertDispatched('play-pause');
});

test('can update current task', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create();

    Livewire::test(Show::class, ['routine' => $routine])
        ->call('updateCurrentTask', 0)
        ->assertSet('currentTask.id', $task->id);
});

test('can handle task not found', function () {
    $routine = Routine::factory()->for($this->user)->create();

    Livewire::test(Show::class, ['routine' => $routine])
        ->call('updateCurrentTask', 999)
        ->assertSet('isFinished', true);
});

test('can go to next task', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task1 = RoutineTask::factory()->for($routine)->create(['order' => 1]);
    $task2 = RoutineTask::factory()->for($routine)->create(['order' => 2]);

    Livewire::test(Show::class, ['routine' => $routine])
        ->set('currentTaskIndex', 0)
        ->call('next')
        ->assertSet('currentTaskIndex', 1)
        ->assertDispatched('start-timer');
});

test('can handle timer finished with autoskip', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create([
        'autoskip' => true,
    ]);

    Livewire::test(Show::class, ['routine' => $routine])
        ->set('currentTask', $task)
        ->set('currentTaskIndex', 0)
        ->call('onTimerFinished')
        ->assertSet('currentTaskIndex', 1);
});

test('can handle timer finished without autoskip', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create([
        'autoskip' => false,
    ]);

    Livewire::test(Show::class, ['routine' => $routine])
        ->set('currentTask', $task)
        ->set('currentTaskIndex', 0)
        ->call('onTimerFinished')
        ->assertSet('currentTaskIndex', 0);
});

test('can get current task property', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create();

    $component = Livewire::test(Show::class, ['routine' => $routine]);
    $component->set('currentTaskIndex', 0);

    $currentTask = $component->instance()->getCurrentTaskProperty();
    $this->assertNotNull($currentTask);
    $this->assertEquals($task->id, $currentTask->id);
});

test('can update task order', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task1 = RoutineTask::factory()->for($routine)->create(['order' => 1]);
    $task2 = RoutineTask::factory()->for($routine)->create(['order' => 2]);

    Livewire::test(Show::class, ['routine' => $routine])
        ->call('updateTaskOrder', [$task2->id, $task1->id])
        ->assertDispatched('task-updated');

    $task1->refresh();
    $task2->refresh();
    $this->assertEquals(2, $task1->order);
    $this->assertEquals(1, $task2->order);
});

test('can delete task', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create();

    Livewire::test(Show::class, ['routine' => $routine])
        ->call('deleteTask', $task);

    $this->assertSoftDeleted('routine_tasks', ['id' => $task->id]);
});

test('can duplicate task', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create([
        'name' => 'Original Task',
        'description' => 'Original Description',
    ]);

    Livewire::test(Show::class, ['routine' => $routine])
        ->call('duplicateTask', $task);

    $this->assertDatabaseHas('routine_tasks', [
        'name' => 'Original Task (copy)',
        'description' => 'Original Description',
    ]);
});

test('can handle task saved event', function () {
    $routine = Routine::factory()->for($this->user)->create();

    Livewire::test(Show::class, ['routine' => $routine])
        ->call('onTaskSaved');
});
