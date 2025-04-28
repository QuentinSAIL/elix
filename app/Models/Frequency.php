<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Frequency extends Model
{
    use hasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function routines()
    {
        return $this->hasMany(Routine::class);
    }
}
