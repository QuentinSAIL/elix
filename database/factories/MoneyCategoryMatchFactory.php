<?php

namespace Database\Factories;

use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoneyCategoryMatch>
 */
class MoneyCategoryMatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MoneyCategoryMatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'money_category_id' => MoneyCategory::factory(),
            'keyword' => $this->faker->word(),
        ];
    }
}
