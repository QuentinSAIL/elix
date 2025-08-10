<?php

use App\Livewire\Auth\ConfirmPassword;
use Livewire\Livewire;

test('confirm password component can be rendered', function () {
    Livewire::test(ConfirmPassword::class)
        ->assertStatus(200);
});
