<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\User;
use App\Models\Routine;
use App\Models\Frequency;
use App\Models\RoutineTask;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Quentin l\'admin',
            'email' => 'test@example.com',
        ]);

        Routine::factory()
        ->count(10)
        ->create()
        ->each(function ($routine) {
            echo "Routine : " .  $routine->id . " : " . $routine->frequency->summary() . "\n";
        });

        Note::factory()
        ->count(50)
        ->create();
    }
}
