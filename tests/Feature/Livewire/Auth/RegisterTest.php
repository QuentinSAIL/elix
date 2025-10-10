<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('register component can be rendered', function () {
    Livewire::test(Register::class)
        ->assertStatus(200);
});

test('can register new user', function () {
    Event::fake();

    Livewire::test(Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Event::assertDispatched(Registered::class);
});

test('registration fails when registration is disabled', function () {
    config(['app.registration_enabled' => false]);

    Livewire::test(Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['registration' => 'Registration is not open yet.']);

    $this->assertDatabaseMissing('users', [
        'email' => 'john@example.com',
    ]);
});

test('registration fails with invalid data', function () {
    Livewire::test(Register::class)
        ->set('name', '')
        ->set('email', 'invalid-email')
        ->set('password', 'short')
        ->set('password_confirmation', 'different')
        ->call('register')
        ->assertHasErrors([
            'name' => 'required',
            'email' => 'email',
            'password' => 'confirmed',
        ]);
});

test('registration fails with existing email', function () {
    User::factory()->create(['email' => 'john@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['email' => 'unique']);
});
