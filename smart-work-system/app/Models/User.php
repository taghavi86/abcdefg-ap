<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'national_code',
        'email',
        'password',
        'level',
        'coins',
        'total_earnings',
        'accuracy_score',
        'speed_score',
        'is_active',
        'last_level_test_at',
        'consecutive_days',
        'last_activity_at',
        'referral_code',
        'referred_by',
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
            'last_level_test_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                $user->referral_code = substr(md5(uniqid(rand(), true)), 0, 10);
            }
        });
    }

    // Relationships
    public function levelData(): HasOne
    {
        return $this->hasOne(UserLevel::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(UserResponse::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function weeklyRankings(): HasMany
    {
        return $this->hasMany(WeeklyRanking::class);
    }

    public function createdImages(): HasMany
    {
        return $this->hasMany(Image::class, 'created_by');
    }

    // Accessors
    public function getIncomeMultiplierAttribute(): float
    {
        return match($this->level) {
            'base' => 1.0,
            'advanced' => 2.0,
            'elite' => 3.5,
            default => 1.0,
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }
}
