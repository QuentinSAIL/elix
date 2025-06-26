<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function panels()
    {
        return $this->hasMany(MoneyDashboardPanel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
