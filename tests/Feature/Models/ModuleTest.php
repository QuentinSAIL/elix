<?php

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('module can be created', function () {
    $module = Module::factory()->create([
        'name' => 'Test Module',
        'description' => 'A test module',
        'image' => 'test-image.png',
        'is_premium' => true,
        'endpoint' => 'test-endpoint',
    ]);

    $this->assertDatabaseHas('modules', [
        'name' => 'Test Module',
        'description' => 'A test module',
        'image' => 'test-image.png',
        'is_premium' => true,
        'endpoint' => 'test-endpoint',
    ]);
});

test('module has many users', function () {
    $module = Module::factory()->create();
    $user = User::factory()->create();
    $module->users()->attach($user);

    $this->assertCount(1, $module->users);
    $this->assertTrue($module->users->first() instanceof User);
});

test('is_premium attribute is cast to boolean', function () {
    $module = Module::factory()->create(['is_premium' => 1]);
    $this->assertTrue($module->is_premium);

    $module = Module::factory()->create(['is_premium' => 0]);
    $this->assertFalse($module->is_premium);
});
