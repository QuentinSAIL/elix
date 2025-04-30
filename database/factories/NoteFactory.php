<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use DavidBadura\FakerMarkdownGenerator\FakerProvider;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create();
        $faker->addProvider(new FakerProvider($faker));
        return [
            'user_id' => User::all()->random(),
            'content' => $faker->markdown()
        ];
    }
}
