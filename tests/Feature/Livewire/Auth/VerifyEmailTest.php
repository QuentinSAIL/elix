<?php

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Auth\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;

test('verify email component can be rendered', function () {
    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test(VerifyEmail::class)
        ->assertStatus(200);
});

test('can send verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test(VerifyEmail::class)
        ->call('sendVerification');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('redirects to dashboard if email is already verified', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(VerifyEmail::class)
        ->call('sendVerification')
        ->assertRedirect(route('dashboard', absolute: false));
});

test('can logout', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(VerifyEmail::class)
        ->call('logout')
        ->assertRedirect('/');
});
