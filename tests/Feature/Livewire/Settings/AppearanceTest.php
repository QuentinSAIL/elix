<?php

use App\Livewire\Settings\Appearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('appearance component can be rendered', function () {
    Livewire::test(Appearance::class)
        ->assertStatus(200);
});
