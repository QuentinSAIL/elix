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
        $types = ['bar', 'doughnut', 'pie', 'line', 'table', 'number', 'gauge', 'trend', 'category_comparison'];
        $periodTypes = ['all', 'daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'biannual', 'yearly', 'actual_month', 'previous_month', 'two_months_ago', 'three_months_ago'];

        return [
            'money_dashboard_id' => MoneyDashboard::factory(),
            'title' => $this->faker->sentence(),
            'type' => $this->faker->randomElement($types),
            'period_type' => $this->faker->randomElement($periodTypes),
            'order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
