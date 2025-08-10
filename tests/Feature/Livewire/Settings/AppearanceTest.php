<?php

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Settings\Appearance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('appearance component can be rendered', function () {
    Livewire::test(Appearance::class)
        ->assertStatus(200);
});
