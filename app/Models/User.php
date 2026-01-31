<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, TwoFactorAuthenticatable;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
            'permissions' => 'array',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions;

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        if (! is_array($permissions)) {
            return false;
        }

        return in_array($permission, $permissions);
    }

    public function receivings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Receivings::class, 'received_by');
    }

    public function outgoings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Outgoings::class, 'issued_by');
    }
}
