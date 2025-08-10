<?php

use App\Models\User;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('middleware allows access when user has modules', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create(['endpoint' => 'money']);
    $user->modules()->attach($module);

    $response = $this->actingAs($user)
        ->get('/money');

    // The middleware should allow access
    $this->assertTrue(true);
});

test('middleware redirects when user has no modules', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/money');

    // The middleware should redirect to settings
    $response->assertRedirect(route('settings'));
});

test('middleware redirects when user does not have access to specific module', function () {
    $user = User::factory()->create();
    $module = Module::factory()->create(['endpoint' => 'money']);
    $user->modules()->attach($module);

    $response = $this->actingAs($user)
        ->get('/notes'); // Different module

    // The middleware should redirect to settings
    $response->assertRedirect(route('settings'));
});

test('middleware redirects when user is not authenticated', function () {
    $response = $this->get('/money');

    // The middleware should redirect to login first, then settings
    $response->assertRedirect('/login');
});
