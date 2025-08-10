<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'timezone',
        'language',
        'date_of_birth',
        'gender',
        'phone',
        'country',
        'is_active',
        'preferences',
        'ai_settings',
        'email_notifications',
        'marketing_emails',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'recovery_codes',
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
            'date_of_birth' => 'date',
            'last_login_at' => 'datetime',
            'tier_expires_at' => 'datetime',
            'first_chat_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'preferences' => 'array',
            'ai_settings' => 'array',
            'recovery_codes' => 'array',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'email_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is moderator
     */
    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }







    /**
     * Get user's AI assistants
     */
    public function aiAssistants(): HasMany
    {
        return $this->hasMany(AIAssistant::class);
    }

    /**
     * Get user's chats
     */
    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    /**
     * Get user's transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get user's blog posts
     */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    /**
     * Get avatar URL with fallback
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        // Generate Gravatar URL
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }

    /**
     * Get user's tier name
     */
    public function getTierNameAttribute(): string
    {
        $tiers = [
            1 => 'Basic',
            2 => 'Premium',
            3 => 'Pro',
            4 => 'Enterprise',
        ];

        return $tiers[$this->current_tier] ?? 'Basic';
    }

    /**
     * Check if user's tier is active
     */
    public function isTierActive(): bool
    {
        return !$this->tier_expires_at || $this->tier_expires_at->isFuture();
    }

    /**
     * Get user's display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: 'Anonymous User';
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified users
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope for users with credits
     */
    public function scopeWithCredits($query, int $minAmount = 1)
    {
        return $query->where('credits_balance', '>=', $minAmount);
    }

    /**
     * Check if account is locked due to failed login attempts
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until > now();
    }

    /**
     * Increment failed login attempts
     */
    public function incrementFailedLogins(): void
    {
        $this->increment('failed_login_attempts');

        // Lock account after 5 failed attempts
        if ($this->failed_login_attempts >= 5) {
            $this->update([
                'locked_until' => now()->addMinutes(30)
            ]);
        }
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedLogins(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Add credits to user balance
     */
    public function addCredits(int $credits, string $reason = 'Manual adjustment'): void
    {
        $this->increment('credits_balance', $credits);
        $this->increment('total_credits_purchased', $credits);
        
        // Record analytics
        Analytics::record('credits_purchased', $credits, (string)$this->id);
        
        // Log the transaction
        \Log::info('Credits added to user', [
            'user_id' => $this->id,
            'credits' => $credits,
            'reason' => $reason,
            'new_balance' => $this->fresh()->credits_balance,
        ]);
    }

    /**
     * Deduct credits from user balance
     */
    public function deductCredits(int $credits): void
    {
        if ($this->credits_balance < $credits) {
            throw new \Exception('Insufficient credits');
        }
        
        $this->decrement('credits_balance', $credits);
        $this->increment('total_credits_used', $credits);
        
        // Record analytics
        Analytics::record('credits_consumed', $credits, (string)$this->id);
    }

    /**
     * Check if user has enough credits
     */
    public function hasCredits(int $required): bool
    {
        return $this->credits_balance >= $required;
    }

    /**
     * Get user's favorite AI assistants
     */
    public function favoriteAiAssistants()
    {
        return $this->belongsToMany(AIAssistant::class, 'user_favorites')
                    ->withTimestamps();
    }

    /**
     * Get user's active chats
     */
    public function activeChats()
    {
        return $this->chats()->where('is_archived', false);
    }

    /**
     * Get user's ratings
     */
    public function aiRatings()
    {
        return $this->hasMany(AIRating::class);
    }

    /**
     * Check if user is VIP (has premium tier)
     */
    public function isVip(): bool
    {
        return $this->current_tier > 1 && 
               ($this->tier_expires_at === null || $this->tier_expires_at->isFuture());
    }

    /**
     * Upgrade user tier
     */
    public function upgradeTier(int $tier, ?\Carbon\Carbon $expiresAt = null): void
    {
        $this->update([
            'current_tier' => max($this->current_tier, $tier),
            'tier_expires_at' => $expiresAt,
        ]);
    }
}
