<?php

use App\Livewire\Auth\ForgotPassword;
use Livewire\Livewire;

test('forgot password component can be rendered', function () {
    Livewire::test(ForgotPassword::class)
        ->assertStatus(200);
});
