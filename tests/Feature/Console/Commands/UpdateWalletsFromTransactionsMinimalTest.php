<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\UpdateWalletsFromTransactions;
use Tests\TestCase;

class UpdateWalletsFromTransactionsMinimalTest extends TestCase
{
    #[test]
    public function it_can_be_instantiated()
    {
        $command = new UpdateWalletsFromTransactions();
        $this->assertInstanceOf(UpdateWalletsFromTransactions::class, $command);
    }
}
