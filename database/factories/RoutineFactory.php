<?php

namespace Database\Factories;

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
        return [
            'user_id' => User::all()->random(),
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'start_datetime' => $this->faker->dateTime,
            'end_datetime' => $this->faker->dateTime,
            'frequency_id' => Frequency::where('name', 'Daily')->first()->id,
            'is_active' => $this->faker->boolean,
        ];
    }
}
