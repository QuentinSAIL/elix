<?php

namespace App\Livewire\Routine;

use Flux\Flux;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Livewire\Component;
use App\Models\Routine;
use App\Models\Frequency;
use Illuminate\Support\Facades\Auth;
use Masmerise\Toaster\Toaster;

class Index extends Component
{
    public $user;
    public $routines;

    public $newRoutine = [
        'name' => '',
        'description' => '',
        'is_active' => true,
    ];

    public $days = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    public $newFrequency = [
        'start_date' => '',
        'end_date' => null,
        'end_type' => 'never', // never|until_date|occurrences
        'occurrence_count' => null,
        'interval' => 1,
        'unit' => 'day',
        'weekdays' => [],
        'month_days' => [],
        'month_occurrences' => [],
    ];

    public $endTypes = [
        'never' => 'Jamais',
        'until_date' => 'Jusqu\'à la date',
        'occurrences' => 'Nombre d\'occurrences',
    ];

    public $units = [
        'day' => 'Jour(s)',
        'week' => 'Semaine(s)',
        'month' => 'Mois(s)',
        'year' => 'Année(s)',
    ];

    public $freqMonthTypes = [
        'daysNum' => 'Jours fixes',
        'ordinal' => 'ordinales',
    ];

    public $freqMonthTypesOrdinalList = [
        -1 => 'Dernier',
        1 => 'Premier',
        2 => 'Deuxième',
        3 => 'Troisième',
        4 => 'Quatrième',
    ];

    public $freqMonthType = 'daysNum';

    public function mount()
    {
        $this->user = Auth::user();
        $this->resetForm();
        $this->routines = $this->user->routines()->with('frequency')->get();
    }

    public function updateMonthType()
    {
        if ($this->freqMonthType === 'daysNum') {
            $this->newFrequency['month_occurrences'] = [];
        } else {
            $this->newFrequency['month_days'] = [];
            $this->newFrequency['month_occurrences'] = [
                ['ordinal' => 1, 'weekday' => 1],
            ];
        }
    }

    public function toggleWeekday(int $day)
    {
        $days = $this->newFrequency['weekdays'] ?? [];

        if (in_array($day, $days)) {
            $this->newFrequency['weekdays'] = array_values(array_diff($days, [$day]));
        } else {
            $days[] = $day;
            sort($days);
            $this->newFrequency['weekdays'] = $days;
        }
    }

    public function toggleMonthDay(int $day)
    {
        $days = $this->newFrequency['month_days'] ?? [];

        if (in_array($day, $days)) {
            $this->newFrequency['month_days'] = array_values(array_diff($days, [$day]));
        } else {
            $days[] = $day;
            sort($days);
            $this->newFrequency['month_days'] = $days;
        }
    }

    /**
     * Computed property Livewire : disponible en blade sous $frequencySummary
     */
    public function getFrequencySummaryProperty(): string
    {
        // On ne garde que les champs pertinents pour summary()
        $attrs = Arr::only($this->newFrequency, [
            'start_date',
            'end_date',
            'end_type',
            'occurrence_count',
            'interval',
            'unit',
            'weekdays',
            'month_days',
            'month_occurrences',
        ]);


        if (!empty($this->newFrequency['start_date'])) {
            $attrs['start_date'] = Carbon::parse($this->newFrequency['start_date']);
        }

        if (!empty($this->newFrequency['end_at'])) {
            $attrs['end_date'] = Carbon::parse($this->newFrequency['end_at']);
        }

        $freq = new Frequency($attrs);
        return $freq->summary();
    }

    public function delete(string $id)
    {
        if ($r = Routine::find($id)) {
            $r->delete();
            Toaster::success("La routine « {$r->name} » a été supprimée.");
            $this->routines = $this->routines->filter(fn($n) => $n->id !== $id);
        }
    }

    public function create()
    {
        // règles de validation
        $rules = [
            'newRoutine.name' => 'required|string|max:255',
            'newRoutine.is_active' => 'boolean',

            'newFrequency.start_date' => 'required|date',
            'newFrequency.end_date' => 'nullable|date|after_or_equal:newFrequency.start_at',
            'newFrequency.end_type' => 'required|in:never,until_date,occurrences',
            'newFrequency.occurrence_count' => 'nullable|integer|min:1',
            'newFrequency.interval' => 'required|integer|min:1',
            'newFrequency.unit' => 'required|in:day,week,month,year',
            'newFrequency.weekdays' => 'nullable|array',
            'newFrequency.month_days' => 'nullable|array',
            'newFrequency.month_occurrences' => 'nullable|array',
        ];

        if ($this->newFrequency['unit'] === 'week') {
            $rules['newFrequency.weekdays.*'] = 'integer|between:1,7';
        }
        if ($this->newFrequency['unit'] === 'month') {
            if ($this->freqMonthType === 'days') {
                $rules['newFrequency.month_days.*'] = 'integer|between:1,31';
            } else {
                $rules['newFrequency.month_occurrences.0.ordinal'] = 'required|in:-1,1,2,3,4';
                $rules['newFrequency.month_occurrences.0.weekday'] = 'required|integer|between:1,7';
            }
        }

        $this->validate($rules);

        // créer la fréquence
        $freqData = $this->newFrequency;
        if ($freqData['unit'] !== 'week') {
            $freqData['weekdays'] = [];
        }
        if ($freqData['unit'] !== 'month' || $this->freqMonthType === 'occurrences') {
            $freqData['month_days'] = [];
        }
        if ($freqData['unit'] !== 'month' || $this->freqMonthType === 'days') {
            $freqData['month_occurrences'] = [];
        }
        $frequency = Frequency::create($freqData);

        // créer la routine
        $routine = $this->user->routines()->create([
            'name' => $this->newRoutine['name'],
            'description' => $this->newRoutine['description'],
            'frequency_id' => $frequency->id,
            'is_active' => $this->newRoutine['is_active'],
        ]);

        Toaster::success("La routine « {$routine->name} » a bien été créée !");
        $this->routines->push($routine);
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->newRoutine = [
            'name' => '',
            'description' => '',
            'is_active' => true,
        ];
        $this->newFrequency = [
        'start_date' => '',
        'end_date' => null,
        'end_type' => 'never',
        'occurrence_count' => null,
        'interval' => 1,
        'unit' => 'day',
        'weekdays' => [],
        'month_days' => [],
        'month_occurrences' => [],
        ];
        $this->freqMonthType = 'days';
        $this->newFrequency['start_date'] = now()->setTimezone(config('app.timezone'))->addMinutes(15 - (now()->minute % 15))->format('Y-m-d H:i');
    }

    public function render()
    {
        return view('livewire.routine.index');
    }
}
