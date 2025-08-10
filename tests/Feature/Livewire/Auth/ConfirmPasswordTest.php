<?php

use Livewire\Livewire;
use App\Livewire\Auth\ConfirmPassword;

test('confirm password component can be rendered', function () {
    Livewire::test(ConfirmPassword::class)
        ->assertStatus(200);
});