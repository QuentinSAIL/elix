<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\TestTransactionPerformance;
use Tests\TestCase;

class TestTransactionPerformanceMinimalTest extends TestCase
{
    #[test]
    public function it_can_be_instantiated()
    {
        $command = new TestTransactionPerformance;
        $this->assertInstanceOf(TestTransactionPerformance::class, $command);
    }
}
