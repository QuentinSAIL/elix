<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankTransactions>
 */
class BankTransactionsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BankTransactions::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_account_id' => BankAccount::factory(),
            'gocardless_transaction_id' => $this->faker->uuid(),
            'amount' => $this->faker->randomFloat(2, -1000, 1000),
            'description' => $this->faker->sentence(),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'money_category_id' => null,
        ];
    }
}
