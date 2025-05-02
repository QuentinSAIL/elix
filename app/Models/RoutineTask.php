<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoutineTask extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'routine_id',
        'name',
        'description',
        'reccurence',
        'duration', // in seconds
        'order',
        'autoskip',
        'is_active',
    ];

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
        return ($hours > 0 ? $hours . 'h' : '') . ($minutes > 0 ? $minutes . 'm' : '') . ($seconds > 0 ? $seconds . 's' : '');
    }
}
