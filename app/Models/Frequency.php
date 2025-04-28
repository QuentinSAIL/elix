<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Frequency extends Model
{
    use hasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'cron_expression',
    ];

    public function routines()
    {
        return $this->hasMany(Routine::class);
    }
}
