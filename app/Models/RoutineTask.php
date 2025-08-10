<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class RoutineTask extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'routine_id',
        'name',
        'description',
        'duration', // in seconds
        'order',
        'autoskip',
        'is_active',
    ];

    protected static function booted()
    {
        static::addGlobalScope('userRoutine', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                $builder->whereIn('routine_id', $user->routines->pluck('id'))->orderBy('order', 'asc');
            }
        });
    }

    public function routine()
    {
        return $this->belongsTo(Routine::class);
    }

    public function durationText(): string
    {
        $seconds = $this->duration;
        $seconds = $seconds % 60;
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        return ($hours > 0 ? $hours.'h' : '').($minutes > 0 ? $minutes.'m' : '').($seconds > 0 ? $seconds.'s' : '');
    }
}
