<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'gocardless_account_id' => $this->faker->uuid(),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'end_valid_access' => $this->faker->dateTimeBetween('now', '+1 year'),
            'institution_id' => $this->faker->uuid(),
            'agreement_id' => $this->faker->uuid(),
            'reference' => $this->faker->uuid(),
            'transaction_total_days' => $this->faker->numberBetween(30, 365),
            'iban' => $this->faker->iban('FR'),
            'currency' => 'EUR',
            'owner_name' => $this->faker->name(),
            'cash_account_type' => 'CurrentAccount',
            'logo' => $this->faker->imageUrl(),
        ];
    }
}
