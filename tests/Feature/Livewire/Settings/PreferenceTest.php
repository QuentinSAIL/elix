<?php

use App\Livewire\Settings\Preference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('preference component can be rendered', function () {
    Livewire::test(Preference::class)
        ->assertStatus(200);
});
