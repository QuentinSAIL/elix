<?php

namespace Database\Factories;

use App\Models\Frequency;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class FrequencyFactory extends Factory
{
    protected $model = Frequency::class;

    public function definition()
    {
        $units    = ['day', 'week', 'month', 'year'];
        $unit     = $this->faker->randomElement($units);
        $interval = $this->faker->numberBetween(1, 5);

        // Choix aléatoire du type de fin
        $endType = $this->faker->randomElement(['never', 'until_date', 'occurrences']);

        $data = [
            'name'              => ucfirst($interval)
                                  . ' ' . ($interval > 1 ? Str::plural($unit) : $unit)
                                  . ' #' . $this->faker->unique()->numberBetween(1, 1_000_000),
            'description'       => $this->faker->sentence(),
            'interval'          => $interval,
            'unit'              => $unit,
            'weekdays'          => null,
            'month_days'        => null,
            'month_occurrences' => null,
            'end_type'          => $endType,
            'end_date'          => null,
            'start_date'        => $this->faker->dateTimeBetween('-1 month', 'now'),
            'occurrence_count'  => null,
        ];

        // répétitions par semaine
        if ($unit === 'week') {
            $data['weekdays'] = $this->faker->randomElements(
                [1,2,3,4,5,6,7],
                $this->faker->numberBetween(1, 3)
            );
        }

        // répétitions par mois
        if ($unit === 'month') {
            if ($this->faker->boolean(50)) {
                // jours fixes
                $data['month_days'] = $this->faker->randomElements(
                    range(1, 28),
                    $this->faker->numberBetween(1, 3)
                );
            } else {
                // occurrence ordinales
                $data['month_occurrences'] = [[
                    'ordinal' => $this->faker->randomElement([-1, 1, 2, 3, 4]),
                    'weekday' => $this->faker->numberBetween(1, 7),
                ]];
            }
        }

        // configuration de la fin
        if ($endType === 'until_date') {
            // date arbitraire dans l’année à venir
            $data['end_date'] = $this->faker->dateTimeBetween('now', '+100 year');
        } elseif ($endType === 'occurrences') {
            $data['occurrence_count'] = $this->faker->numberBetween(1, 400);
        }

        return $data;
    }
}
