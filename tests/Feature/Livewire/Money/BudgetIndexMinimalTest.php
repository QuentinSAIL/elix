<?php

namespace Tests\Feature\Livewire\Money;

use App\Livewire\Money\BudgetIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetIndexMinimalTest extends TestCase
{
    use RefreshDatabase;

    #[test]
    public function it_renders_successfully()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(BudgetIndex::class)
            ->assertStatus(200);
    }
}
