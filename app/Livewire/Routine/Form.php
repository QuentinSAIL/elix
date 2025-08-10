<?php

namespace App\Livewire\Routine;

use Flux\Flux;
use Carbon\Carbon;
use App\Models\Routine;
use Livewire\Component;
use App\Models\Frequency;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Form extends Component
{
    public $user;

    public $edition;

    public $routineId;

    public $routine; // c'est rempli quand on est en edition
    public $frequency; // c'est rempli quand on est en edition

    public $routineForm = [
        'name' => '',
        'description' => '',
        'is_active' => true,
        'frequency_id' => null,
    ];

    public $frequencyForm = [
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

    public $days = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
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
    public $freqMonthType = 'daysNum';

    public $freqMonthTypesOrdinalList = [
        -1 => 'Dernier',
        1 => 'Premier',
        2 => 'Deuxième',
        3 => 'Troisième',
        4 => 'Quatrième',
    ];

    public function mount()
    {
        $this->user = Auth::user();
        if ($this->routine) {
            $this->routineId = $this->routine->id;
            $this->edition = true;
            $this->routineForm = [
                'name' => $this->routine->name,
                'description' => $this->routine->description,
                'is_active' => $this->routine->is_active,
                'frequency_id' => $this->routine->frequency_id,
            ];
            $this->frequencyForm = [
                'start_date' => Carbon::parse($this->routine->frequency->start_date)
                    ->setTimezone(config('app.timezone'))
                    ->format('Y-m-d H:i'),
                'end_date' => $this->routine->frequency->end_date,
                'end_type' => $this->routine->frequency->end_type,
                'occurrence_count' => $this->routine->frequency->occurrence_count,
                'interval' => $this->routine->frequency->interval,
                'unit' => $this->routine->frequency->unit,
                'weekdays' => $this->routine->frequency->weekdays,
                'month_days' => $this->routine->frequency->month_days,
                'month_occurrences' => $this->routine->frequency->month_occurrences,
            ];

            $this->freqMonthType = $this->routine->frequency->month_occurrences ? 'ordinal' : 'daysNum';
        } else {
            $this->routineId = 'create';
            $this->frequencyForm['start_date'] = now()
                ->setTimezone(config('app.timezone'))
                ->addMinutes(15 - (now()->minute % 15))
                ->format('Y-m-d H:i');
            $this->edition = false;
        }
    }

    public function updateMonthType()
    {
        if ($this->freqMonthType === 'daysNum') {
            $this->frequencyForm['month_occurrences'] = [];
        } else {
            $this->frequencyForm['month_days'] = [];
            $this->frequencyForm['month_occurrences'] = [['ordinal' => 1, 'weekday' => 1]];
        }
    }

    public function toggleWeekday(int $day)
    {
        $days = $this->frequencyForm['weekdays'] ?? [];

        if (in_array($day, $days)) {
            $this->frequencyForm['weekdays'] = array_values(array_diff($days, [$day]));
        } else {
            $days[] = $day;
            sort($days);
            $this->frequencyForm['weekdays'] = $days;
        }
    }

    public function toggleMonthDay(int $day)
    {
        $days = $this->frequencyForm['month_days'] ?? [];

        if (in_array($day, $days)) {
            $this->frequencyForm['month_days'] = array_values(array_diff($days, [$day]));
        } else {
            $days[] = $day;
            sort($days);
            $this->frequencyForm['month_days'] = $days;
        }
    }

    public function save()
    {
        // règles de validation
        $rules = [
            'routineForm.name' => 'required|string|max:255',
            'routineForm.is_active' => 'boolean',

            'frequencyForm.start_date' => 'required|date',
            'frequencyForm.end_date' => 'nullable|date|after_or_equal:frequencyForm.start_at',
            'frequencyForm.end_type' => 'required|in:never,until_date,occurrences',
            'frequencyForm.occurrence_count' => 'nullable|integer|min:1',
            'frequencyForm.interval' => 'required|integer|min:1',
            'frequencyForm.unit' => 'required|in:day,week,month,year',
            'frequencyForm.weekdays' => 'nullable|array',
            'frequencyForm.month_days' => 'nullable|array',
            'frequencyForm.month_occurrences' => 'nullable|array',
        ];

        if ($this->frequencyForm['unit'] === 'week') {
            $rules['frequencyForm.weekdays.*'] = 'integer|between:1,7';
        }
        if ($this->frequencyForm['unit'] === 'month') {
            if ($this->freqMonthType === 'daysNum') {
                $rules['frequencyForm.month_days.*'] = 'integer|between:1,31';
            } else {
                $rules['frequencyForm.month_occurrences.0.ordinal'] = 'required|in:-1,1,2,3,4';
                $rules['frequencyForm.month_occurrences.0.weekday'] = 'required|integer|between:1,7';
            }
        }

        $this->validate($rules);

        // créer la fréquence
        $freqData = $this->frequencyForm;
        if ($freqData['unit'] !== 'week') {
            $freqData['weekdays'] = [];
        }
        if ($freqData['unit'] !== 'month' || $this->freqMonthType === 'occurrences') {
            $freqData['month_days'] = [];
        }
        if ($freqData['unit'] !== 'month' || $this->freqMonthType === 'daysNum') {
            $freqData['month_occurrences'] = [];
        }
        $frequency = Frequency::create($freqData);

        if ($this->routine) {
            $this->routine->update($this->routineForm);
            $this->routine->frequency()->update($freqData);
            Toaster::success(__('Routine updated successfully.'));
        } else {
            $this->routineForm['frequency_id'] = $frequency->id;
            $this->routine = $this->user->routines()->create($this->routineForm);
            Toaster::success(__('Routine created successfully.'));
        }
        Flux::modals()->close('routine-form-' . $this->routineId);
        $this->dispatch('routine-saved', routine: $this->routine);
    }

    public function resetForm()
    {
        $this->routineForm = [
            'name' => '',
            'description' => '',
            'is_active' => true,
        ];

        $this->frequencyForm = [
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
        $this->freqMonthType = 'daysNum';
        $this->frequencyForm['start_date'] = now()
            ->setTimezone(config('app.timezone'))
            ->addMinutes(15 - (now()->minute % 15))
            ->format('Y-m-d H:i');
    }

    public function getFrequencySummaryProperty(): string
    {
        if (is_null($this->frequencyForm)) {
            return '';
        }
        $attrs = Arr::only($this->frequencyForm, ['start_date', 'end_date', 'end_type', 'occurrence_count', 'interval', 'unit', 'weekdays', 'month_days', 'month_occurrences']);

        if (!empty($this->frequencyForm['start_date'])) {
            $attrs['start_date'] = Carbon::parse($this->frequencyForm['start_date']);
        }

        if (!empty($this->frequencyForm['end_at'])) {
            $attrs['end_date'] = Carbon::parse($this->frequencyForm['end_at']);
        }

        $freq = new Frequency($attrs);
        return $freq->summary();
    }

    public function render()
    {
        return view('livewire.routine.form');
    }
}
