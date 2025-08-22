<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'locale' => $this->faker->locale(),
            'timezone' => $this->faker->timezone(),
            'theme_mode' => $this->faker->randomElement(['light', 'dark']),
        ];
    }
}
