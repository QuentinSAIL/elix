<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Only run scheduled tasks in production
        if (!app()->environment('production')) {
            return;
        }

        // Update price assets twice daily (9:00 and 18:00)
        $schedule->command('prices:update-assets --limit=100')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        $schedule->command('prices:update-assets --limit=100')
            ->dailyAt('18:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // Keep the old command for backward compatibility during transition
        $schedule->command('wallets:update-prices')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // Update bank transactions for all users every night at 2:00 AM
        $schedule->command('bank-transactions:update')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
