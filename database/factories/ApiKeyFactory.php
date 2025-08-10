<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ApiService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiKey>
 */
class ApiKeyFactory extends Factory
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
            'api_service_id' => ApiService::factory(),
            'secret_id' => $this->faker->uuid(),
            'secret_key' => $this->faker->sha256(),
        ];
    }
}