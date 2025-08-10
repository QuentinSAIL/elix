<?php

use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('reset password component can be rendered and mount properties', function () {
    $token = Str::random(60);
    $email = 'test@example.com';

    Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $email])
        ->assertSet('token', $token)
        ->assertSet('email', $email)
        ->assertStatus(200);
});

test('can reset password successfully', function () {
    Event::fake();

    $user = User::factory()->create();
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $user->email])
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertRedirect(route('login'));

    Event::assertDispatched(PasswordReset::class);
});

test('reset password fails with invalid token', function () {
    $user = User::factory()->create();
    $invalidToken = 'invalid-token';

    Livewire::test(ResetPassword::class, ['token' => $invalidToken, 'email' => $user->email])
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasErrors(['email']);
});

test('reset password fails with invalid email', function () {
    $token = Str::random(60);

    Livewire::test(ResetPassword::class, ['token' => $token, 'email' => 'invalid-email'])
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasErrors(['email' => 'email']);
});

test('reset password fails with weak password', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $user->email])
        ->set('password', '123')
        ->set('password_confirmation', '123')
        ->call('resetPassword')
        ->assertHasErrors(['password']);
});

test('reset password fails with mismatched password confirmation', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $user->email])
        ->set('password', 'new-password')
        ->set('password_confirmation', 'different-password')
        ->call('resetPassword')
        ->assertHasErrors(['password' => 'confirmed']);
});
