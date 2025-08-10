<?php

use App\Models\User;
use App\Models\Routine;
use App\Models\Frequency;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('frequency belongs to routine', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $frequency = Frequency::factory()->create();
    $routine->update(['frequency_id' => $frequency->id]);

    $this->assertInstanceOf(Routine::class, $frequency->routine);
    $this->assertEquals($routine->id, $frequency->routine->id);
});

test('can generate summary for daily frequency', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $frequency = Frequency::factory()->create([
        'interval' => 1,
        'unit' => 'day',
    ]);
    $routine->update(['frequency_id' => $frequency->id]);

    $summary = $frequency->summary();
    $this->assertStringContainsString('Chaque jour', $summary);
});

test('can generate summary for weekly frequency', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $frequency = Frequency::factory()->create([
        'interval' => 2,
        'unit' => 'week',
        'weekdays' => [1, 3], // Monday and Wednesday
    ]);
    $routine->update(['frequency_id' => $frequency->id]);

    $summary = $frequency->summary();
    $this->assertStringContainsString('Toutes les 2 semaines', $summary);
    $this->assertStringContainsString('lundi', $summary);
    $this->assertStringContainsString('mercredi', $summary);
});

test('can generate summary for monthly frequency', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $frequency = Frequency::factory()->create([
        'interval' => 1,
        'unit' => 'month',
        'month_days' => [1, 15],
    ]);
    $routine->update(['frequency_id' => $frequency->id]);

    $summary = $frequency->summary();
    $this->assertStringContainsString('Chaque mois', $summary);
    $this->assertStringContainsString('1er', $summary);
    $this->assertStringContainsString('15', $summary);
});

test('can generate summary for monthly frequency with occurrences', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $frequency = Frequency::factory()->create([
        'interval' => 1,
        'unit' => 'month',
        'month_occurrences' => [
            ['ordinal' => 1, 'weekday' => 1], // First Monday
        ],
    ]);
    $routine->update(['frequency_id' => $frequency->id]);

    $summary = $frequency->summary();
    $this->assertStringContainsString('Chaque mois', $summary);
    $this->assertStringContainsString('1er', $summary);
    $this->assertStringContainsString('lundi', $summary);
});

test('can generate summary with start date', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $frequency = Frequency::factory()->create([
        'interval' => 1,
        'unit' => 'day',
        'start_date' => now(),
    ]);
    $routine->update(['frequency_id' => $frequency->id]);

    $summary = $frequency->summary();
    $this->assertStringContainsString('À partir du', $summary);
});

test('can generate summary for yearly frequency', function () {
    $routine = Routine::factory()->for($this->user)->create();
    $frequency = Frequency::factory()->create([
        'interval' => 1,
        'unit' => 'year',
    ]);
    $routine->update(['frequency_id' => $frequency->id]);

    $summary = $frequency->summary();
    $this->assertStringContainsString('Chaque année', $summary);
});
