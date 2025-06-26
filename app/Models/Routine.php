<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Routine extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'frequency_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('userRoutine', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('user_id', Auth::id())->orderBy('created_at', 'desc');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function frequency()
    {
        return $this->belongsTo(Frequency::class);
    }

    public function tasks()
    {
        return $this->hasMany(RoutineTask::class);
    }
}
