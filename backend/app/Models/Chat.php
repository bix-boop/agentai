<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ai_assistant_id',
        'title',
        'settings',
        'message_count',
        'credits_used',
        'last_activity_at',
        'is_archived',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_activity_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    /**
     * Get the user that owns the chat
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI assistant for this chat
     */
    public function aiAssistant(): BelongsTo
    {
        return $this->belongsTo(AIAssistant::class);
    }

    /**
     * Get messages in this chat
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Get recent messages for conversation context
     */
    public function recentMessages(int $limit = null): HasMany
    {
        $limit = $limit ?? $this->aiAssistant->conversation_memory ?? 10;
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Update activity timestamp
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Add credits to the chat usage
     */
    public function addCreditsUsed(int $credits): void
    {
        $this->increment('credits_used', $credits);
    }

    /**
     * Increment message count
     */
    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
    }

    /**
     * Generate title from first message
     */
    public function generateTitle(): void
    {
        if ($this->title) {
            return;
        }

        $firstMessage = $this->messages()->where('role', 'user')->first();
        if ($firstMessage) {
            $title = substr($firstMessage->content, 0, 50);
            if (strlen($firstMessage->content) > 50) {
                $title .= '...';
            }
            $this->update(['title' => $title]);
        }
    }

    /**
     * Scope for active chats
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope for archived chats
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope for recent activity
     */
    public function scopeRecentActivity($query)
    {
        return $query->orderBy('last_activity_at', 'desc');
    }
}
