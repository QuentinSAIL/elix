<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Frequency extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'end_type',
        'end_date',
        'occurrence_count',
        'interval',
        'unit',
        'weekdays',
        'month_days',
        'month_occurrences',
    ];

    protected $casts = [
        'weekdays'         => 'array',
        'month_days'       => 'array',
        'month_occurrences'=> 'array',
        'end_date'         => 'datetime',
    ];

    public function routine()
    {
        return $this->belongsTo(Routine::class);
    }

    /**
     * Retourne une phrase résumé de la fréquence, par ex. :
     * - "Tous les 3 jours"
     * - "Chaque semaine les lundis et mercredis"
     * - "Tous les mois le 1er mardi"
     * - "Chaque année"
     */
    /**
 * Retourne une phrase résumé de la fréquence, par ex. :
 * - "Chaque jour"
 * - "Tous les 2 semaines les lundis et mercredis"
 * - "Chaque mois le 1er mardi jusqu’au 30/06/2025"
 * - "Tous les mois le 1er mardi (max 5 fois)"
 */
public function summary(): string
{
    $int   = $this->interval;
    $unit  = $this->unit;
    $type  = $this->end_type;

    $singular = ['day'=>'jour', 'week'=>'semaine', 'month'=>'mois',   'year'=>'année'];
    $plural   = ['day'=>'jours','week'=>'semaines','month'=>'mois','year'=>'années'];
    $gender   = ['day'=>'tous', 'week'=>'toutes','month'=>'tous', 'year'=>'toutes'];

    // Base de la phrase
    if ($int === 1) {
        $base = "Chaque {$singular[$unit]}";
    } else {
        $base = "{$gender[$unit]} les {$int} {$plural[$unit]}";
    }

    // Spécifique semaine
    if ($unit === 'week' && !empty($this->weekdays)) {
        $jours  = array_map([$this, 'dayName'], $this->weekdays);
        $préfix = count($jours) > 1 ? 'les' : 'le';
        $base  .= " {$préfix} ".implode(', ', $jours);
    }

    // Spécifique mois
    if ($unit === 'month') {
        if (!empty($this->month_occurrences)) {
            $parts = [];
            foreach ($this->month_occurrences as $occ) {
                $parts[] = $this->ordinalLabel($occ['ordinal'])
                         . ' ' . $this->dayName($occ['weekday']);
            }
            $préfix = count($parts) > 1 ? 'les' : 'le';
            $base  .= " {$préfix} ".implode(', ', $parts);

        } elseif (!empty($this->month_days)) {
            $jours  = array_map([$this, 'ordinalSuffix'], $this->month_days);
            $préfix = count($jours) > 1 ? 'les' : 'le';
            $base  .= " {$préfix} ".implode(', ', $jours);
        }
    }

    // Condition de fin
    switch ($type) {
        case 'until_date':
            $date = $this->end_date instanceof \DateTimeInterface
                  ? $this->end_date->format('d/m/Y')
                  : (string) $this->end_date;
            $base .= " jusqu’au {$date}";
            break;

        case 'occurrences':
            $base .= " (max {$this->occurrence_count} fois)";
            break;

        // case 'never': rien à ajouter
    }

    // Première lettre en majuscule
    return ucfirst($base);
}


    /**
     * Mappe 1..7 → noms de jours (lundi=1…dimanche=7)
     */
    protected function dayName(int $d): string
    {
        return [
            1=>'lundi',2=>'mardi',3=>'mercredi',4=>'jeudi',
            5=>'vendredi',6=>'samedi',7=>'dimanche'
        ][$d] ?? '';
    }

    /**
     * Pour les occurrences ordinales :
     *  1 → "1er", 2→"2e", …, -1→"dernier"
     */
    protected function ordinalLabel(int $n): string
    {
        return $n === -1
            ? 'dernier'
            : ($n === 1
                ? '1er'
                : "{$n}e"
              );
    }

    /**
     * Pour les jours fixes du mois :
     * 1 → "1er", 2→"2e", 3→"3e", …
     */
    protected function ordinalSuffix(int $n): string
    {
        return $n === 1 ? '1er' : "{$n}e";
    }
}
