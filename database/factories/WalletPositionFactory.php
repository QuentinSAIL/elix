<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\WalletPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletPositionFactory extends Factory
{
    protected $model = WalletPosition::class;

    public function definition()
    {
        return [
            'wallet_id' => Wallet::factory(),
            'name' => $this->faker->word(),
            'ticker' => $this->faker->lexify('???'),
            'unit' => 'USD',
            'quantity' => $this->faker->randomFloat(8, 0, 100),
            'price' => $this->faker->randomFloat(8, 0, 10000),
        ];
    }
}
