<?php

namespace Database\Factories;

use App\Models\Frequency;
use App\Models\Routine;
use App\Models\RoutineTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

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
            'user_id' => User::first()->id,
            'frequency_id' => $frequency->id,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentences(3, true),
            // 'is_active'         => $this->faker->boolean(80),
            'is_active' => true,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Routine $routine) {
            $count = rand(1, 20);

            RoutineTask::factory()
                ->count($count)
                ->state(new Sequence(
                    ...array_map(
                        fn ($i) => ['order' => $i + 1],
                        range(0, $count - 1)
                    )
                ))
                ->create([
                    'routine_id' => $routine->id,
                ]);
        });
    }
}
