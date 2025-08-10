<?php

namespace Database\Factories;

use App\Models\MoneyDashboard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoneyDashboardPanel>
 */
class MoneyDashboardPanelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['bar', 'doughnut', 'pie', 'line', 'table', 'number'];
        $periodTypes = ['all', 'daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'biannual', 'yearly'];

        return [
            'money_dashboard_id' => MoneyDashboard::factory(),
            'title' => $this->faker->sentence(),
            'is_expense' => $this->faker->boolean(),
            'type' => $this->faker->randomElement($types),
            'period_type' => $this->faker->randomElement($periodTypes),
        ];
    }
}