<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'is_active',
        'show_on_homepage',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_homepage' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (!$category->slug) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get AI assistants in this category
     */
    public function aiAssistants(): HasMany
    {
        return $this->hasMany(AIAssistant::class);
    }

    /**
     * Get active AI assistants in this category
     */
    public function activeAiAssistants(): HasMany
    {
        return $this->aiAssistants()->where('is_active', true)->where('is_public', true);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for homepage categories
     */
    public function scopeHomepage($query)
    {
        return $query->where('show_on_homepage', true)->where('is_active', true);
    }

    /**
     * Scope ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get category with AI assistant count
     */
    public function scopeWithAiCount($query)
    {
        return $query->withCount(['aiAssistants' => function ($query) {
            $query->where('is_active', true)->where('is_public', true);
        }]);
    }

    /**
     * Get the category's icon with fallback
     */
    public function getIconAttribute($value): string
    {
        return $value ?: 'heroicon-o-folder';
    }

    /**
     * Get category URL
     */
    public function getUrlAttribute(): string
    {
        return route('categories.show', $this->slug);
    }
}
