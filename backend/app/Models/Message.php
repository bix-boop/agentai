<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'metadata',
        'credits_consumed',
        'tokens_used',
        'model_used',
        'processing_time',
        'is_edited',
        'is_flagged',
        'flag_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'processing_time' => 'decimal:3',
        'is_edited' => 'boolean',
        'is_flagged' => 'boolean',
    ];

    /**
     * Get the chat that owns this message
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Check if this is a user message
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if this is an assistant message
     */
    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Check if this is a system message
     */
    public function isSystem(): bool
    {
        return $this->role === 'system';
    }

    /**
     * Get character count
     */
    public function getCharacterCount(): int
    {
        return strlen($this->content);
    }

    /**
     * Get word count
     */
    public function getWordCount(): int
    {
        return str_word_count($this->content);
    }

    /**
     * Check if message contains images
     */
    public function hasImages(): bool
    {
        return $this->metadata && 
               isset($this->metadata['type']) && 
               $this->metadata['type'] === 'image_generation' &&
               isset($this->metadata['images']) &&
               is_array($this->metadata['images']) &&
               count($this->metadata['images']) > 0;
    }

    /**
     * Get images from metadata
     */
    public function getImages(): array
    {
        if (!$this->hasImages()) {
            return [];
        }

        return $this->metadata['images'];
    }

    /**
     * Flag message for review
     */
    public function flag(string $reason = null): void
    {
        $this->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
        ]);
    }

    /**
     * Unflag message
     */
    public function unflag(): void
    {
        $this->update([
            'is_flagged' => false,
            'flag_reason' => null,
        ]);
    }

    /**
     * Mark message as edited
     */
    public function markAsEdited(): void
    {
        $this->update(['is_edited' => true]);
    }

    /**
     * Scope for user messages
     */
    public function scopeUser($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope for assistant messages
     */
    public function scopeAssistant($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Scope for system messages
     */
    public function scopeSystem($query)
    {
        return $query->where('role', 'system');
    }

    /**
     * Scope for flagged messages
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Scope for recent messages
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope for messages with credits consumed
     */
    public function scopeWithCredits($query)
    {
        return $query->where('credits_consumed', '>', 0);
    }

    /**
     * Get formatted processing time
     */
    public function getFormattedProcessingTimeAttribute(): string
    {
        if (!$this->processing_time) {
            return 'N/A';
        }

        if ($this->processing_time < 1) {
            return round($this->processing_time * 1000) . 'ms';
        }

        return round($this->processing_time, 2) . 's';
    }

    /**
     * Get message preview (first 100 characters)
     */
    public function getPreviewAttribute(): string
    {
        return strlen($this->content) > 100 
            ? substr($this->content, 0, 100) . '...'
            : $this->content;
    }
}
