<?php

namespace Database\Factories;

use App\Models\Routine;
use App\Models\RoutineTask;
use App\Models\User;
use App\Models\Frequency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Routine>
 */
class RoutineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frequency = Frequency::factory()->create();
        return [
            'user_id'           => User::first()->id,
            'frequency_id'      => $frequency->id,
            'name'              => $this->faker->words(3, true),
            'description'       => $this->faker->sentence(),
            'is_active'         => $this->faker->boolean(80),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Routine $routine) {
            RoutineTask::factory()
                ->count(rand(1, 10))
                ->create([
                    'routine_id' => $routine->id,
                ]);
        });
    }
}
