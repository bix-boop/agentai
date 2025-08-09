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
        'amount_cents',
        'currency',
        'credits',
        'description',
        'payment_data',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'payment_data' => 'array',
        'processed_at' => 'datetime',
        'amount_cents' => 'integer',
        'credits' => 'integer',
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
        return $this->amount_cents / 100;
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
