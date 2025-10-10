<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoneyCategory>
 */
class MoneyCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'color' => $this->faker->hexColor(),
            'budget' => $this->faker->randomFloat(2, 0, 1000),
            'include_in_dashboard' => $this->faker->boolean(),
        ];
    }
}
