<?php

namespace Tests\Feature\Livewire\Money;

use App\Livewire\Money\WalletForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WalletFormMinimalTest extends TestCase
{
    use RefreshDatabase;

    #[test]
    public function it_renders_successfully()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(WalletForm::class)
            ->assertStatus(200);
    }
}
