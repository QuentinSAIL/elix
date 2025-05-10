<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApiKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'service',
        'secret_id',
        'secret_key',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
