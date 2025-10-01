<?php

use App\Livewire\Settings\DeleteUserForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);
    $this->actingAs($this->user);
});

test('delete user form component can be rendered', function () {
    Livewire::test(DeleteUserForm::class)
        ->assertStatus(200);
});

test('can delete user with correct password', function () {
    Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertRedirect('/');

    $this->assertSoftDeleted('users', ['id' => $this->user->id]);
});

test('cannot delete user with incorrect password', function () {
    Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password' => 'current_password']);
});

test('cannot delete user without password', function () {
    Livewire::test(DeleteUserForm::class)
        ->set('password', '')
        ->call('deleteUser')
        ->assertHasErrors(['password' => 'required']);
});

test('validates password is required', function () {
    Livewire::test(DeleteUserForm::class)
        ->call('deleteUser')
        ->assertHasErrors(['password' => 'required']);
});
