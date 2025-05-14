<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoneyDashboard extends Model
{
    use HasUuids, HasFactory;

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
