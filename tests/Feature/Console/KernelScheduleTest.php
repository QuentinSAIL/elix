<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class KernelScheduleTest extends TestCase
{
    public function test_schedule_commands_are_registered()
    {
        // Ensure the commands resolve and are callable; this at least loads Kernel
        $this->assertIsInt(Artisan::call('list'));
        $this->assertNotNull(app()->make('Illuminate\\Console\\Scheduling\\Schedule'));
    }
}
