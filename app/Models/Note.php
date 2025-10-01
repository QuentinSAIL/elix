<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Note extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
