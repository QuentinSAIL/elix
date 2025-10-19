<?php

namespace Database\Factories;

use App\Models\PriceAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceAsset>
 */
class PriceAssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PriceAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticker' => $this->faker->unique()->regexify('[A-Z]{3,5}'),
            'type' => $this->faker->randomElement(['STOCK', 'CRYPTO', 'ETF', 'BOND', 'COMMODITY']),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'price' => $this->faker->randomFloat(2, 1, 1000),
            'last_updated' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}

