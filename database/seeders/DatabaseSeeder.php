<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Routine;
use App\Models\RoutineTask;
use App\Models\Frequency;
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

        Frequency::create([
            'name' => 'Daily',
            'description' => 'Runs every day at the same time',
        ]);
        Frequency::create([
            'name' => 'Weekly',
            'description' => 'Runs every week on the same day and time',
        ]);
        Frequency::create([
            'name' => 'Monthly',
            'description' => 'Runs every month on the same day and time',
        ]);
        Frequency::create([
            'name' => 'Yearly',
            'description' => 'Runs every year on the same day and time',
        ]);
        Frequency::create([
            'name' => 'Every 2 Days',
            'description' => 'Runs every 2 days at the same time',
        ]);
        Frequency::create([
            'name' => 'Weekly Custom Day',
            'description' => 'Runs every week on the selected day at the same time',
        ]);

        Routine::factory(10)->create()->each(function ($routine) {
            RoutineTask::factory(10)->create([
                'routine_id' => $routine->id,
            ]);
        });
    }
}
