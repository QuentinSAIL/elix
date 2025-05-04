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
        return [
            'routine_id' => null,
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'duration' => $this->faker->numberBetween(1, 3600),
            'order' => 0,
            'autoskip' => true,
            'is_active' => true,
        ];
    }
}
