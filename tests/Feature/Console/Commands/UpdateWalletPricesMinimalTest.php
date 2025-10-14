<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\UpdateWalletPrices;
use Tests\TestCase;

class UpdateWalletPricesMinimalTest extends TestCase
{
    #[test]
    public function it_can_be_instantiated()
    {
        $command = new UpdateWalletPrices;
        $this->assertInstanceOf(UpdateWalletPrices::class, $command);
    }
}
