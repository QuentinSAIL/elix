<?php

namespace App\Http\Livewire\Traits;

use Masmerise\Toaster\Toaster;

trait Notifies
{
    public function notifySuccess(string $message): void
    {
        Toaster::success($message);
    }

    public function notifyError(string $message): void
    {
        Toaster::error($message);
    }

    public function notifyInfo(string $message): void
    {
        Toaster::info($message);
    }

    public function notifyWarning(string $message): void
    {
        Toaster::warning($message);
    }
}
