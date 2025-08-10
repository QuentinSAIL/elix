<?php

use App\Models\User;
use Livewire\Livewire;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use App\Livewire\Settings\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('profile component can be rendered', function () {
    Livewire::test(Profile::class)
        ->assertStatus(200);
});

test('can mount with user data', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->assertSet('name', 'John Doe')
        ->assertSet('email', 'john.doe@example.com');
});

test('can update profile information', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane.doe@example.com')
        ->call('updateProfileInformation')
        ->assertDispatched('profile-updated');

    $user->refresh();
    $this->assertSame('Jane Doe', $user->name);
    $this->assertSame('jane.doe@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('validates required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('name', '')
        ->call('updateProfileInformation')
        ->assertHasErrors(['name' => 'required']);
});

test('validates email format', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('email', 'invalid-email')
        ->call('updateProfileInformation')
        ->assertHasErrors(['email' => 'email']);
});

test('validates unique email', function () {
    $user1 = User::factory()->create(['email' => 'test1@example.com']);
    $user2 = User::factory()->create(['email' => 'test2@example.com']);

    Livewire::actingAs($user1)
        ->test(Profile::class)
        ->set('email', 'test2@example.com')
        ->call('updateProfileInformation')
        ->assertHasErrors(['email' => 'unique']);
});

test('can resend email verification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->call('resendVerificationNotification');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('redirects to dashboard if email is already verified', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->call('resendVerificationNotification')
        ->assertRedirect(route('dashboard', absolute: false));
});
