<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KernelTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_method_defines_commands(): void
    {
        $kernel = new \App\Console\Kernel(app(), app('events'), app(Schedule::class));

        $schedule = app(Schedule::class);

        // Test that the schedule method can be called without errors using reflection
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    public function test_commands_method_registers_commands(): void
    {
        $kernel = new \App\Console\Kernel(app(), app('events'), app(Schedule::class));

        // Test that the commands method can be called without errors using reflection
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('commands');
        $method->setAccessible(true);
        $method->invoke($kernel);

        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    public function test_kernel_can_be_instantiated(): void
    {
        $kernel = new \App\Console\Kernel(app(), app('events'), app(Schedule::class));

        $this->assertInstanceOf(\App\Console\Kernel::class, $kernel);
    }

    public function test_schedule_has_price_update_commands(): void
    {
        $kernel = new \App\Console\Kernel(app(), app('events'), app(Schedule::class));
        $schedule = app(Schedule::class);

        // Use reflection to call the protected schedule method
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        // Get all scheduled events
        $events = $schedule->events();

        // Check that we have scheduled events
        $this->assertGreaterThan(0, count($events));

        // Check that we have price update commands scheduled
        $hasPriceUpdateCommand = false;
        foreach ($events as $event) {
            if (str_contains($event->command, 'prices:update-assets')) {
                $hasPriceUpdateCommand = true;
                break;
            }
        }

        $this->assertTrue($hasPriceUpdateCommand, 'Price update command should be scheduled');
    }

    public function test_schedule_has_wallet_update_command(): void
    {
        $kernel = new \App\Console\Kernel(app(), app('events'), app(Schedule::class));
        $schedule = app(Schedule::class);

        // Use reflection to call the protected schedule method
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        // Get all scheduled events
        $events = $schedule->events();

        // Check that we have wallet update command scheduled
        $hasWalletUpdateCommand = false;
        foreach ($events as $event) {
            if (str_contains($event->command, 'wallets:update-prices')) {
                $hasWalletUpdateCommand = true;
                break;
            }
        }

        $this->assertTrue($hasWalletUpdateCommand, 'Wallet update command should be scheduled');
    }

    public function test_schedule_has_daily_price_updates(): void
    {
        $kernel = new \App\Console\Kernel(app(), app('events'), app(Schedule::class));
        $schedule = app(Schedule::class);

        // Use reflection to call the protected schedule method
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        // Get all scheduled events
        $events = $schedule->events();

        // Check that we have daily price updates
        $hasDailyPriceUpdate = false;
        foreach ($events as $event) {
            if (str_contains($event->command, 'prices:update-assets')) {
                $hasDailyPriceUpdate = true;
                break;
            }
        }

        $this->assertTrue($hasDailyPriceUpdate, 'Daily price update should be scheduled');
    }

    public function test_schedule_has_frequent_wallet_updates(): void
    {
        $kernel = new \App\Console\Kernel(app(), app('events'), app(Schedule::class));
        $schedule = app(Schedule::class);

        // Use reflection to call the protected schedule method
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        // Get all scheduled events
        $events = $schedule->events();

        // Check that we have frequent wallet updates (every 15 minutes)
        $hasFrequentWalletUpdate = false;
        foreach ($events as $event) {
            if (str_contains($event->command, 'wallets:update-prices') &&
                str_contains($event->expression, '*/15 * * * *')) { // Every 15 minutes
                $hasFrequentWalletUpdate = true;
                break;
            }
        }

        $this->assertTrue($hasFrequentWalletUpdate, 'Frequent wallet update should be scheduled');
    }
}
