<?php

use App\Livewire\Settings\Modules;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('modules component can be rendered', function () {
    $module = Module::factory()->create();

    Livewire::test(Modules::class)
        ->assertStatus(200)
        ->assertSee($module->name);
});

test('can mount with user modules', function () {
    $module1 = Module::factory()->create();
    $module2 = Module::factory()->create();
    $this->user->modules()->attach($module1);

    Livewire::test(Modules::class)
        ->assertSet('allModules', Module::all())
        ->assertSet('activeModules', [(string) $module1->id]);
});

test('can mount without user modules', function () {
    $module = Module::factory()->create();

    Livewire::test(Modules::class)
        ->assertSet('activeModules', []);
});

test('can update modules', function () {
    $module1 = Module::factory()->create();
    $module2 = Module::factory()->create();

    Livewire::test(Modules::class)
        ->set('activeModules', [(string) $module1->id, (string) $module2->id])
        ->call('updateModules');

    $this->user->refresh();
    $this->assertCount(2, $this->user->modules);
    $this->assertTrue($this->user->modules->contains($module1));
    $this->assertTrue($this->user->modules->contains($module2));
});

test('can remove all modules', function () {
    $module = Module::factory()->create();
    $this->user->modules()->attach($module);

    Livewire::test(Modules::class)
        ->set('activeModules', [])
        ->call('updateModules');

    $this->user->refresh();
    $this->assertCount(0, $this->user->modules);
});

test('can toggle individual modules', function () {
    $module1 = Module::factory()->create();
    $module2 = Module::factory()->create();
    $this->user->modules()->attach($module1);

    Livewire::test(Modules::class)
        ->set('activeModules', [(string) $module2->id])
        ->call('updateModules');

    $this->user->refresh();
    $this->assertCount(1, $this->user->modules);
    $this->assertTrue($this->user->modules->contains($module2));
    $this->assertFalse($this->user->modules->contains($module1));
});
