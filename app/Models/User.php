<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasAuditFields, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'failed_login_attempts',
        'suspended_until',
        'email_verified_at',
        'profile_photo_path',
        'profile_photo_disk',
        'has_no_password',
        'profile_completed_at',
        'is_verified_by_admin',
        'verified_by',
        'verified_at',
        'password',
    ];

    protected $hidden = [
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active'               => 'boolean',
            'failed_login_attempts'   => 'integer',
            'suspended_until'         => 'datetime',
            'has_no_password'          => 'boolean',
            'profile_completed_at'    => 'datetime',
            'is_verified_by_admin'    => 'integer',
            'verified_at'             => 'datetime',
        ];
    }

    public function hasPassword(): bool
    {
        return !is_null($this->password);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function verifier(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'verified_by');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function sentNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->whereNull('read_at');
    }
}