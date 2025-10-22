<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MoneyDashboard extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'order',
        'size',
        'position_x',
        'position_y',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('user_id', Auth::id());
            }
        });
    }

    public function panels()
    {
        return $this->hasMany(MoneyDashboardPanel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
