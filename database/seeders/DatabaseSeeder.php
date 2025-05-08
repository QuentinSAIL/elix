<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\User;
use App\Models\Routine;
use App\Models\Frequency;
use App\Models\BankAccount;
use App\Models\RoutineTask;
use Illuminate\Support\Str;
use App\Models\MoneyCategory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'id' => '0196a75d-7ef3-7152-99e6-28a673e336c5',
            'name' => 'Quentin l\'admin',
            'email' => 'test@example.com',
        ]);

        Routine::factory()
        ->count(50)
        ->create()
        ->each(function ($routine) {
            // echo "Routine : " .  $routine->id . " : " . $routine->frequency->summary() . "\n";
        });

        Note::factory()
        ->count(50)
        ->create();

        MoneyCategory::factory()
        ->count(50)
        ->create();

    }
}
