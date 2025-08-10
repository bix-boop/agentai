<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFavorite extends Model
{
    use HasFactory;

    protected $table = 'user_favorites';

    protected $fillable = [
        'user_id',
        'ai_assistant_id',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI assistant
     */
    public function aiAssistant(): BelongsTo
    {
        return $this->belongsTo(AIAssistant::class);
    }

    /**
     * Check if user has favorited an AI assistant
     */
    public static function isFavorited(int $userId, int $aiAssistantId): bool
    {
        return self::where('user_id', $userId)
                   ->where('ai_assistant_id', $aiAssistantId)
                   ->exists();
    }

    /**
     * Toggle favorite status
     */
    public static function toggle(int $userId, int $aiAssistantId): bool
    {
        $favorite = self::where('user_id', $userId)
                        ->where('ai_assistant_id', $aiAssistantId)
                        ->first();

        if ($favorite) {
            $favorite->delete();
            return false; // Unfavorited
        } else {
            self::create([
                'user_id' => $userId,
                'ai_assistant_id' => $aiAssistantId,
            ]);
            return true; // Favorited
        }
    }
}