<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'whatsapp_number',
        'password',
        'is_verified',
        'avatar',
        'profile_cover',
        'role',
        'plan_id',
        'credits',
        'credits_used',
        'plan_expires_at',
        'country_code',
        'country_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'plan_expires_at'   => 'datetime',
        'is_verified'       => 'boolean',
        'credits'           => 'integer',
        'credits_used'      => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function presentations()
    {
        return $this->hasMany(Presentation::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function toolJobs()
    {
        return $this->hasMany(ToolJob::class);
    }

    // ─── Credit Helpers ───────────────────────────────────────────────────────

    /**
     * Check if user has enough credits for a given amount.
     */
    public function hasCredits(int $amount): bool
    {
        return ($this->credits ?? 0) >= $amount;
    }

    /**
     * Deduct credits from the user (atomically).
     */
    public function deductCredits(int $amount): bool
    {
        if (!$this->hasCredits($amount)) {
            return false;
        }

        $this->decrement('credits', $amount);
        $this->increment('credits_used', $amount);

        return true;
    }

    /**
     * Refund credits to the user (e.g. on job failure).
     */
    public function refundCredits(int $amount): void
    {
        $this->increment('credits', $amount);
        $this->decrement('credits_used', $amount);
    }

    // ─── Role Helpers ─────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPlanActive(): bool
    {
        if ($this->plan_expires_at === null) {
            return true; // Free plan never expires
        }
        return $this->plan_expires_at->isFuture();
    }
}
