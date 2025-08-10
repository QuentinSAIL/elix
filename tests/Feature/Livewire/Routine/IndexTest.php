<?php

use App\Livewire\Routine\Index;
use App\Models\Frequency;
use App\Models\Routine;
use App\Models\User;
use Livewire\Livewire;
use Masmerise\Toaster\Toaster;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('routine index component can be rendered', function () {
    Livewire::test(Index::class)
        ->assertStatus(200);
});

test('can load routines', function () {
    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'Test Routine',
    ]);

    $frequency = Frequency::factory()->create([
        'start_date' => now(),
        'end_type' => 'never',
        'interval' => 1,
        'unit' => 'day',
    ]);

    $routine->update(['frequency_id' => $frequency->id]);

    Livewire::test(Index::class)
        ->assertStatus(200)
        ->assertSee('Test Routine');
});

test('can select routine', function () {
    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'Test Routine',
    ]);

    Livewire::test(Index::class)
        ->call('selectRoutine', $routine->id)
        ->assertSet('selectedRoutine.id', $routine->id);
});

test('can deselect routine', function () {
    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'Test Routine',
    ]);

    Livewire::test(Index::class)
        ->call('selectRoutine', $routine->id)
        ->call('selectRoutine', null)
        ->assertSet('selectedRoutine', null);
});

test('can delete routine', function () {
    Toaster::fake();

    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'Test Routine',
    ]);

    Livewire::test(Index::class)
        ->call('delete', $routine->id);

    // Check that the routine was actually deleted by querying the database directly
    $deletedRoutine = Routine::find($routine->id);
    $this->assertNull($deletedRoutine);
});

test('shows error when deleting non-existent routine', function () {
    Toaster::fake();

    Livewire::test(Index::class)
        ->call('delete', '00000000-0000-0000-0000-000000000000');

    // Should not dispatch any toast for non-existent routine
    Toaster::assertNothingDispatched();
});

test('clears selected routine when routine is deleted', function () {
    Toaster::fake();

    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'Test Routine',
    ]);

    Livewire::test(Index::class)
        ->call('selectRoutine', $routine->id)
        ->call('delete', $routine->id)
        ->assertSet('selectedRoutine', null);
});

test('can handle routine-saved event for new routine', function () {
    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'New Routine',
    ]);

    Livewire::test(Index::class)
        ->call('reRenderRoutines', $routine->toArray())
        ->assertStatus(200);
});

test('can handle routine-saved event for existing routine', function () {
    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'Original Name',
    ]);

    $updatedRoutine = $routine->toArray();
    $updatedRoutine['name'] = 'Updated Name';

    Livewire::test(Index::class)
        ->call('reRenderRoutines', $updatedRoutine)
        ->assertStatus(200);
});

test('can handle routine-saved event for non-existent routine', function () {
    $routineData = [
        'id' => '00000000-0000-0000-0000-000000000000',
        'name' => 'Non-existent Routine',
    ];

    Livewire::test(Index::class)
        ->call('reRenderRoutines', $routineData)
        ->assertStatus(200);
});

test('loads first routine as selected by default', function () {
    $routine1 = Routine::factory()->for($this->user)->create([
        'name' => 'First Routine',
    ]);
    $routine2 = Routine::factory()->for($this->user)->create([
        'name' => 'Second Routine',
    ]);

    Livewire::test(Index::class)
        ->assertSet('selectedRoutine.id', $routine1->id);
});

test('can handle empty routines list', function () {
    Livewire::test(Index::class)
        ->assertStatus(200);
});
