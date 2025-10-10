<?php

namespace Tests\Unit\Models;

use App\Models\RoutineTask;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoutineTaskTest extends TestCase
{
    #[Test]
    public function test_duration_text_returns_correct_format()
    {
        $task = new RoutineTask;

        $task->duration = 30;
        $this->assertEquals('30s', $task->durationText());

        $task->duration = 120; // 2 minutes
        $this->assertEquals('2m', $task->durationText());

        $task->duration = 3600; // 1 hour
        $this->assertEquals('1h', $task->durationText());

        $task->duration = 3720; // 1 hour 2 minutes
        $this->assertEquals('1h2m', $task->durationText());

        $task->duration = 3725; // 1 hour 2 minutes 5 seconds
        $this->assertEquals('1h2m5s', $task->durationText());

        $task->duration = 125; // 2 minutes 5 seconds
        $this->assertEquals('2m5s', $task->durationText());

        $task->duration = 0;
        $this->assertEquals('', $task->durationText());
    }
}
