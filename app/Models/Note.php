<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Note extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'user_id',
        'content',
    ];

    protected static function booted()
    {
        static::addGlobalScope('userNotes', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('user_id', Auth::id())->orderBy('created_at', 'desc');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
