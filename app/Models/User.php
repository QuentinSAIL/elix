<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Note;
use App\Models\Routine;
use Illuminate\Support\Str;
use App\Models\BankTransactions;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function routines()
    {
        return $this->hasMany(Routine::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function bankTransactions()
    {
        return $this->hasManyThrough(BankTransactions::class, BankAccount::class);
    }

    public function sumBalances()
    {
        return $this->bankAccounts->sum('balance');
    }

    public function moneyCategories()
    {
        return $this->hasMany(MoneyCategory::class);
    }

    public function moneyCategoryMatches()
    {
        return $this->hasMany(MoneyCategoryMatch::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }
}
