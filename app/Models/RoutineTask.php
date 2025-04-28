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
}
