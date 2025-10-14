<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic price updates
Schedule::command('wallets:update-prices')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Log::error('Failed to update wallet prices automatically');
    });

// Schedule cache cleanup for old price data
Schedule::command('cache:clear')
    ->dailyAt('02:00')
    ->description('Clear old price cache daily');
