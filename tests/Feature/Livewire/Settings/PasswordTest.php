<?php

use App\Livewire\Settings\Password;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);
    $this->actingAs($this->user);
});

test('password component can be rendered', function () {
    Livewire::test(Password::class)
        ->assertStatus(200);
});

test('can update password with correct current password', function () {
    $component = Livewire::test(Password::class)
        ->set('current_password', 'current-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password');
    $component->call('updatePassword');

    $this->user->refresh();
    $this->assertTrue(Hash::check('new-password', $this->user->password));
});

test('cannot update password with incorrect current password', function () {
    Livewire::test(Password::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password' => 'current_password']);
});

test('cannot update password without current password', function () {
    Livewire::test(Password::class)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password' => 'required']);
});

test('cannot update password with weak password', function () {
    Livewire::test(Password::class)
        ->set('current_password', 'current-password')
        ->set('password', '123')
        ->set('password_confirmation', '123')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});

test('cannot update password with mismatched confirmation', function () {
    Livewire::test(Password::class)
        ->set('current_password', 'current-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'different-password')
        ->call('updatePassword')
        ->assertHasErrors(['password' => 'confirmed']);
});

test('resets form fields after successful update', function () {
    Livewire::test(Password::class)
        ->set('current_password', 'current-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertSet('current_password', '')
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '');
});

test('resets form fields after validation error', function () {
    Livewire::test(Password::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertSet('current_password', '')
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '');
});
