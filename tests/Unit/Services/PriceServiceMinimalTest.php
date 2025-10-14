<?php

namespace Tests\Unit\Services;

use App\Services\PriceService;
use Tests\TestCase;

class PriceServiceMinimalTest extends TestCase
{
    #[test]
    public function it_can_be_instantiated()
    {
        $service = new PriceService;
        $this->assertInstanceOf(PriceService::class, $service);
    }
}
