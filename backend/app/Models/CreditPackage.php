<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'credits',
        'price_cents',
        'currency',
        'tier',
        'features',
        'is_popular',
        'is_active',
        'sort_order',
        'discount_percentage',
        'valid_until',
    ];

    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'price_cents' => 'integer',
        'credits' => 'integer',
        'tier' => 'integer',
        'sort_order' => 'integer',
        'discount_percentage' => 'decimal:2',
        'valid_until' => 'datetime',
    ];

    // Relationships
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Accessors
    public function getPriceAttribute()
    {
        return $this->price_cents / 100;
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' ' . strtoupper($this->currency);
    }

    public function getCreditsPerDollarAttribute()
    {
        return $this->price > 0 ? round($this->credits / $this->price, 2) : 0;
    }

    public function getDiscountedPriceAttribute()
    {
        if ($this->discount_percentage > 0) {
            return $this->price_cents * (1 - $this->discount_percentage / 100);
        }
        return $this->price_cents;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeByTier($query, $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeInPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price_cents', [$min * 100, $max * 100]);
    }

    // Helper methods
    public function isOnSale(): bool
    {
        return $this->discount_percentage > 0 && 
               ($this->valid_until === null || $this->valid_until->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function calculateSavings(): float
    {
        if ($this->discount_percentage > 0) {
            return ($this->price_cents * $this->discount_percentage / 100) / 100;
        }
        return 0;
    }
}
