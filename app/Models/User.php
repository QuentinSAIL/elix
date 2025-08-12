<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property Collection<int, \App\Models\Module> $modules
 * @property Collection<int, \App\Models\Routine> $routines
 * @property Collection<int, \App\Models\Note> $notes
 * @property Collection<int, \App\Models\BankAccount> $bankAccounts
 * @property Collection<int, \App\Models\BankTransactions> $bankTransactions
 * @property Collection<int, \App\Models\MoneyCategory> $moneyCategories
 * @property Collection<int, \App\Models\MoneyCategoryMatch> $moneyCategoryMatches
 * @property Collection<int, \App\Models\MoneyDashboard> $moneyDashboards
 * @property Collection<int, \App\Models\ApiKey> $apiKeys
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

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

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->map(fn (string $name) => Str::of($name)->substr(0, 1))->implode('');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class)->withTimestamps();
    }

    public function hasModule(string $name): bool
    {
        return $this->modules()->where('name', $name)->exists();
    }

    public function routines(): HasMany
    {
        return $this->hasMany(Routine::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function bankTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(BankTransactions::class, BankAccount::class);
    }

    public function sumBalances(): float
    {
        return $this->bankAccounts->sum('balance');
    }

    public function moneyCategories(): HasMany
    {
        return $this->hasMany(MoneyCategory::class);
    }

    public function moneyCategoryMatches(): HasMany
    {
        return $this->hasMany(MoneyCategoryMatch::class);
    }

    public function moneyDashboards(): HasMany
    {
        return $this->hasMany(MoneyDashboard::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function hasApiKey(string|int $service): bool
    {
        return $this->apiKeys()->where('api_service_id', $service)->exists();
    }
}
