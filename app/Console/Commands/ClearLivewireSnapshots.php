<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLivewireSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-livewire-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $snapshotPath = storage_path('app/livewire-tmp');

        if (! file_exists($snapshotPath)) {
            $this->info('No snapshots directory found.');

            return 0;
        }

        $files = glob($snapshotPath.'/*');
        $count = count($files);

        if ($count === 0) {
            $this->info('No snapshots to clear.');

            return 0;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->info("Cleared {$count} snapshot(s).");

        return 0;
    }
}
