<?php

use App\Models\User;
use App\Models\Routine;
use App\Models\Frequency;
use Livewire\Livewire;
use App\Livewire\Routine\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('routine form component can be rendered', function () {
    Livewire::test(Form::class)
        ->assertStatus(200);
});

test('can mount with new routine', function () {
    Livewire::test(Form::class)
        ->assertSet('edition', null)
        ->assertSet('routineForm.name', '')
        ->assertSet('routineForm.description', '')
        ->assertSet('routineForm.is_active', true);
});

test('can mount with existing routine', function () {
    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'Test Routine',
        'description' => 'Test Description',
        'is_active' => true,
    ]);
    $frequency = Frequency::factory()->create();
    $routine->update(['frequency_id' => $frequency->id]);

    Livewire::test(Form::class, ['routine' => $routine])
        ->assertSet('edition', true)
        ->assertSet('routineForm.name', 'Test Routine')
        ->assertSet('routineForm.description', 'Test Description');
});

test('can reset form', function () {
    Livewire::test(Form::class)
        ->call('resetForm')
        ->assertSet('routineForm.name', '')
        ->assertSet('routineForm.description', '')
        ->assertSet('routineForm.is_active', true)
        ->assertSet('frequencyForm.start_date', now()->addMinutes(15 - (now()->minute % 15))->format('Y-m-d H:i'))
        ->assertSet('frequencyForm.end_date', null);
});

test('can save new routine', function () {
    Livewire::test(Form::class)
        ->set('routineForm', [
            'name' => 'New Routine',
            'description' => 'New Description',
            'is_active' => true,
            'frequency_id' => null,
        ])
        ->set('frequencyForm', [
            'start_date' => now()->format('Y-m-d H:i'),
            'end_date' => null,
            'end_type' => 'never',
            'occurrence_count' => null,
            'interval' => 1,
            'unit' => 'day',
            'weekdays' => [],
            'month_days' => [],
            'month_occurrences' => [],
        ])
        ->call('save');

    $this->assertDatabaseHas('routines', [
        'name' => 'New Routine',
        'description' => 'New Description',
    ]);
});

test('can save existing routine', function () {
    $routine = Routine::factory()->for($this->user)->create([
        'name' => 'Old Name',
    ]);
    $frequency = Frequency::factory()->create();
    $routine->update(['frequency_id' => $frequency->id]);

    Livewire::test(Form::class, ['routine' => $routine])
        ->set('routineForm.name', 'Updated Name')
        ->call('save');

    $routine->refresh();
    $this->assertEquals('Updated Name', $routine->name);
});

test('validates required fields', function () {
    $component = Livewire::test(Form::class);
    $component->set('routineForm.name', '');
    $component->call('save');
    $this->assertTrue($component->errors()->has('routineForm.name'));
});

test('can toggle weekday', function () {
    Livewire::test(Form::class)
        ->set('frequencyForm.weekdays', [])
        ->call('toggleWeekday', 1)
        ->assertSet('frequencyForm.weekdays', [1])
        ->call('toggleWeekday', 1)
        ->assertSet('frequencyForm.weekdays', []);
});

test('can toggle month day', function () {
    Livewire::test(Form::class)
        ->set('frequencyForm.month_days', [])
        ->call('toggleMonthDay', 15)
        ->assertSet('frequencyForm.month_days', [15])
        ->call('toggleMonthDay', 15)
        ->assertSet('frequencyForm.month_days', []);
});

test('can update month type', function () {
    Livewire::test(Form::class)
        ->set('freqMonthType', 'daysNum')
        ->call('updateMonthType')
        ->assertSet('freqMonthType', 'daysNum');
});

test('can get frequency summary', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $frequency = Frequency::factory()->create([
        'interval' => 1,
        'unit' => 'day',
    ]);
    $routine->update(['frequency_id' => $frequency->id]);

    $component = Livewire::test(Form::class, ['routine' => $routine]);
    $this->assertStringContainsString('Chaque jour', $component->get('frequencySummary'));
});
