<?php

use App\Livewire\RoutineTask\Form;
use App\Models\Routine;
use App\Models\RoutineTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    // Truncate the routine_tasks table to ensure a clean state
    DB::table('routine_tasks')->truncate();
    RoutineTask::withoutGlobalScope('userRoutine');
});

test('routine task form component can be rendered', function () {
    $routine = Routine::factory()->for($this->user)->create();

    Livewire::test(Form::class, ['routine' => $routine])
        ->assertStatus(200);
});

test('can reset form', function () {
    $routine = Routine::factory()->for($this->user)->create();

    Livewire::test(Form::class, ['routine' => $routine])
        ->call('resetForm')
        ->assertSet('taskForm.name', '')
        ->assertSet('taskForm.description', '')
        ->assertSet('taskForm.duration', 60)
        ->assertSet('taskForm.order', 1)
        ->assertSet('taskForm.autoskip', true)
        ->assertSet('taskForm.is_active', true);
});

test('can populate form for new task', function () {
    $routine = Routine::factory()->for($this->user)->create();

    Livewire::test(Form::class, ['routine' => $routine])
        ->call('populateForm')
        ->assertSet('edition', false)
        ->assertSet('taskForm.name', '')
        ->assertSet('taskForm.duration', 60);
});

test('can populate form for existing task', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $task = RoutineTask::factory()->for($routine)->create([
        'name' => 'Test Task',
        'description' => 'Test Description',
        'duration' => 120,
        'order' => 1,
        'autoskip' => false,
        'is_active' => true,
    ]);

    Livewire::test(Form::class, ['routine' => $routine, 'task' => $task])
        ->call('populateForm')
        ->assertSet('edition', true)
        ->assertSet('taskForm.name', 'Test Task')
        ->assertSet('taskForm.description', 'Test Description')
        ->assertSet('taskForm.duration', 120);
});

test('can save new task', function () {
    $routine = Routine::factory()->for($this->user)->create();

    Livewire::test(Form::class, ['routine' => $routine])
        ->set('taskForm.name', 'New Task')
        ->set('taskForm.description', 'New Description')
        ->set('duration', 90)
        ->set('taskForm.is_active', true)
        ->call('save');

    $this->assertDatabaseHas('routine_tasks', [
        'name' => 'New Task',
        'description' => 'New Description',
        'duration' => 90,
    ]);
});

test('validates required fields', function () {
    $routine = Routine::factory()->for($this->user)->create();

    Livewire::test(Form::class, ['routine' => $routine])
        ->set('taskForm.name', '')
        ->call('save')
        ->assertHasErrors(['taskForm.name' => 'required']);
});

test('validates duration minimum', function () {
    $routine = Routine::factory()->for($this->user)->create();

    Livewire::test(Form::class, ['routine' => $routine])
        ->set('duration', 0)
        ->call('save')
        ->assertHasErrors(['taskForm.duration' => 'min']);
});

test('validates order minimum', function () {
    $routine = Routine::factory()->for($this->user)->create();

    $component = Livewire::test(Form::class, ['routine' => $routine]);
    $component->set('taskForm.order', 0);
    $component->call('save');
    $this->assertTrue($component->errors()->has('taskForm.order'));
});

test('sets default order for new task', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $existingTask = RoutineTask::factory()->for($routine)->create(['order' => 5]);

    $component = Livewire::test(Form::class, ['routine' => $routine]);
    $component->call('resetForm');
    $component->assertSet('taskForm.order', 1);
});

test('sets order to 1 for first task', function () {
    $routine = Routine::factory()->for($this->user)->create();

    $component = Livewire::test(Form::class, ['routine' => $routine]);
    $component->call('resetForm');
    $component->assertSet('taskForm.order', 1);
});
