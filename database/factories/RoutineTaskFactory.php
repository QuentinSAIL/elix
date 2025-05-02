<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoutineTask>
 */
class RoutineTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $order = 1;

        return [
            'routine_id' => null,
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'reccurence' => $this->faker->randomElement(['tout le temps', 'toutes les deux fois', 'toutes les trois fois']),
            'duration' => $this->faker->numberBetween(1, 36), // in seconds
            'order' => $order++,
            'autoskip' => $this->faker->boolean,
            'is_active' => $this->faker->boolean,
        ];
    }
}
