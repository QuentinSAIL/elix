<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $secret_id
 * @property string $secret_key
 */
class ApiKey extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'api_service_id',
        'secret_id',
        'secret_key',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function apiService()
    {
        return $this->belongsTo(ApiService::class);
    }
}
