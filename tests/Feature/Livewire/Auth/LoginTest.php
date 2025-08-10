<?php

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Auth\Login;

test('login component can be rendered', function () {
    Livewire::test(Login::class)
        ->assertStatus(200);
});

test('users can authenticate', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::shouldReceive('tooManyAttempts')
        ->once()
        ->andReturn(true);

    RateLimiter::shouldReceive('availableIn')
        ->once()
        ->andReturn(60); // 60 seconds

    Event::spy();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email' => __('auth.throttle', [
            'seconds' => 60,
            'minutes' => 1,
        ])]);

    Event::assertDispatched(Lockout::class);
});
