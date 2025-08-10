<?php

use Livewire\Livewire;
use App\Livewire\Auth\ForgotPassword;

test('forgot password component can be rendered', function () {
    Livewire::test(ForgotPassword::class)
        ->assertStatus(200);
});