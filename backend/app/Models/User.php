<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = [
        'name', 'email', 'password', 'oauth_provider', 'oauth_provider_id',
        'timezone', 'is_admin', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin'          => 'boolean',
        'password'          => 'hashed',
    ];

    public function resumes(): HasMany { return $this->hasMany(Resume::class); }
    public function applications(): HasMany { return $this->hasMany(Application::class); }
    public function autoApplyRules(): HasMany { return $this->hasMany(AutoApplyRule::class); }

    public function baseResume(): HasOne
    {
        return $this->hasOne(Resume::class)->where('is_base', true);
    }

    public function settings(): HasMany { return $this->hasMany(Setting::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function creditTransactions(): HasMany { return $this->hasMany(CreditTransaction::class); }
    public function jobProfile(): HasOne { return $this->hasOne(JobProfile::class); }
}
