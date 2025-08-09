<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'credit_package_id',
        'transaction_id',
        'type',
        'status',
        'payment_method',
        'credits_amount',
        'price_cents',
        'currency',
        'discount_applied',
        'gateway_transaction_id',
        'gateway_response',
        'payment_intent_id',
        'invoice_url',
        'bank_reference',
        'bank_verified_at',
        'verified_by',
        'metadata',
        'notes',
        'ip_address',
        'user_agent',
        'processed_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'bank_verified_at' => 'datetime',
        'price_cents' => 'integer',
        'credits_amount' => 'integer',
        'discount_applied' => 'decimal:2',
    ];

    protected $dates = [
        'processed_at',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class);
    }

    // Accessors
    public function getAmountAttribute()
    {
        return $this->price_cents / 100;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . strtoupper($this->currency);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
            'notes' => $reason,
        ]);
    }
}
