<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\ClearLivewireSnapshots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearLivewireSnapshotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully(): void
    {
        $this->artisan(ClearLivewireSnapshots::class)
            ->assertExitCode(0);
    }

    public function test_command_clears_snapshots(): void
    {
        // Create directory if it doesn't exist
        $snapshotDir = storage_path('app/livewire-tmp');
        if (! \Illuminate\Support\Facades\File::exists($snapshotDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($snapshotDir, 0755, true);
        }

        // Create some snapshots
        \Illuminate\Support\Facades\File::put(storage_path('app/livewire-tmp/snapshot1'), 'test');
        \Illuminate\Support\Facades\File::put(storage_path('app/livewire-tmp/snapshot2'), 'test');

        $this->artisan(ClearLivewireSnapshots::class)
            ->assertExitCode(0);

        $this->assertFalse(\Illuminate\Support\Facades\File::exists(storage_path('app/livewire-tmp/snapshot1')));
        $this->assertFalse(\Illuminate\Support\Facades\File::exists(storage_path('app/livewire-tmp/snapshot2')));
    }
}
