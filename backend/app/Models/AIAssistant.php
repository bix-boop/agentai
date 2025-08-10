<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AIAssistant extends Model
{
    use HasFactory;

    protected $table = 'ai_assistants';

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'description',
        'expertise',
        'welcome_message',
        'avatar',
        'system_prompt',
        'temperature',
        'frequency_penalty',
        'presence_penalty',
        'max_tokens',
        'model',
        'min_message_length',
        'max_message_length',
        'conversation_memory',
        'enable_voice',
        'enable_image_generation',
        'enable_web_search',
        'supported_languages',
        'response_tones',
        'writing_styles',
        'is_public',
        'required_packages',
        'minimum_tier',
        'is_active',
        'content_filter_enabled',
        'blocked_words',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'frequency_penalty' => 'decimal:2',
        'presence_penalty' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'enable_voice' => 'boolean',
        'enable_image_generation' => 'boolean',
        'enable_web_search' => 'boolean',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'content_filter_enabled' => 'boolean',
        'supported_languages' => 'array',
        'response_tones' => 'array',
        'writing_styles' => 'array',
        'required_packages' => 'array',
        'blocked_words' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($assistant) {
            if (!$assistant->slug) {
                $assistant->slug = Str::slug($assistant->name);
            }
        });

        static::updating(function ($assistant) {
            if ($assistant->isDirty('name') && !$assistant->isDirty('slug')) {
                $assistant->slug = Str::slug($assistant->name);
            }
        });
    }

    /**
     * Get the AI assistant's creator
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI assistant's category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get chats for this AI assistant
     */
    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    /**
     * Get ratings for this AI assistant
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(AIRating::class, 'ai_assistant_id');
    }

    /**
     * Get public ratings for this AI assistant
     */
    public function publicRatings(): HasMany
    {
        return $this->ratings()->where('is_public', true);
    }

    /**
     * Get users who favorited this AI assistant
     */
    public function favoritedBy(): HasMany
    {
        return $this->hasMany(UserFavorite::class, 'ai_assistant_id');
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Check if user can access this AI assistant
     */
    public function canAccess(User $user): bool
    {
        if (!$this->is_active || !$this->is_public) {
            return false;
        }

        // Check tier requirement
        if ($user->current_tier < $this->minimum_tier) {
            return false;
        }

        // Check package requirements
        if ($this->required_packages && !empty($this->required_packages)) {
            // Check if user has any of the required packages
            $userPackages = $user->transactions()
                ->where('status', 'completed')
                ->whereIn('credit_package_id', $this->required_packages)
                ->exists();
            
            if (!$userPackages) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get AI assistant URL
     */
    public function getUrlAttribute(): string
    {
        return route('ai-assistants.show', $this->slug);
    }

    /**
     * Get chat URL for this AI assistant
     */
    public function getChatUrlAttribute(): string
    {
        return route('chat.start', $this->slug);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Update rating
     */
    public function updateRating(float $rating): void
    {
        $this->increment('total_ratings');
        
        $newAverage = (($this->average_rating * ($this->total_ratings - 1)) + $rating) / $this->total_ratings;
        $this->update(['average_rating' => round($newAverage, 2)]);
    }

    /**
     * Scope for active AI assistants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public AI assistants
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('is_active', true);
    }

    /**
     * Scope for AI assistants accessible by user
     */
    public function scopeAccessibleBy($query, User $user)
    {
        return $query->public()->where('minimum_tier', '<=', $user->current_tier);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for popular AI assistants
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Scope for top rated AI assistants
     */
    public function scopeTopRated($query, int $limit = 10)
    {
        return $query->where('total_ratings', '>', 0)
                    ->orderBy('average_rating', 'desc')
                    ->limit($limit);
    }

    /**
     * Scope for recently created AI assistants
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Search AI assistants
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('expertise', 'like', "%{$term}%");
        });
    }

    /**
     * Get default supported languages
     */
    public function getDefaultLanguages(): array
    {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
        ];
    }

    /**
     * Get default response tones
     */
    public function getDefaultTones(): array
    {
        return [
            'formal' => 'Formal',
            'friendly' => 'Friendly',
            'educational' => 'Educational',
            'humorous' => 'Humorous',
            'professional' => 'Professional',
        ];
    }

    /**
     * Get default writing styles
     */
    public function getDefaultStyles(): array
    {
        return [
            'narrative' => 'Narrative',
            'poetic' => 'Poetic',
            'argumentative' => 'Argumentative',
            'journalistic' => 'Journalistic',
            'academic' => 'Academic',
            'conversational' => 'Conversational',
            'technical' => 'Technical',
            'creative' => 'Creative',
        ];
    }

    /**
     * Calculate and update rating statistics
     */
    public function updateRatingStats(): void
    {
        $ratings = $this->publicRatings();
        $totalRatings = $ratings->count();
        $averageRating = $totalRatings > 0 ? $ratings->avg('rating') : 0;
        
        $this->update([
            'average_rating' => round($averageRating, 2),
            'total_ratings' => $totalRatings,
        ]);
    }

    /**
     * Check if user has favorited this AI
     */
    public function isFavoritedBy(User $user): bool
    {
        return UserFavorite::isFavorited($user->id, $this->id);
    }

    /**
     * Get rating distribution
     */
    public function getRatingDistribution(): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        $ratings = $this->publicRatings()
                        ->selectRaw('rating, COUNT(*) as count')
                        ->groupBy('rating')
                        ->pluck('count', 'rating')
                        ->toArray();
        
        return array_merge($distribution, $ratings);
    }

    /**
     * Increment usage count and record analytics
     */
    public function recordUsage(User $user): void
    {
        $this->increment('usage_count');
        
        // Record analytics
        Analytics::record('ai_assistant_usage', 1, (string)$this->id);
        Analytics::record('category_views', 1, (string)$this->category_id);
    }

    /**
     * Get avatar URL with fallback
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && \Storage::exists($this->avatar)) {
            return \Storage::url($this->avatar);
        }
        
        // Generate a default avatar based on the assistant's name
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . 
               '&color=ffffff&background=' . substr(md5($this->name), 0, 6) . 
               '&size=256&font-size=0.5';
    }
}
