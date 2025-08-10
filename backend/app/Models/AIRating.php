<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIRating extends Model
{
    use HasFactory;

    protected $table = 'ai_ratings';

    protected $fillable = [
        'user_id',
        'ai_assistant_id',
        'rating',
        'review',
        'is_public',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_public' => 'boolean',
    ];

    /**
     * Get the user who made this rating
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI assistant being rated
     */
    public function aiAssistant(): BelongsTo
    {
        return $this->belongsTo(AIAssistant::class);
    }

    /**
     * Scope for public ratings
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for specific rating value
     */
    public function scopeRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get star display for rating
     */
    public function getStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }
}