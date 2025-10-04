<?php

namespace Tests\Unit\Models;

use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class WalletPositionMinimalTest extends TestCase
{
    use RefreshDatabase;

    
    #[test]
    public function it_can_be_instantiated()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create();

        $this->assertInstanceOf(WalletPosition::class, $position);
    }
}