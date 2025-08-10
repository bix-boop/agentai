<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'metric',
        'dimension',
        'value',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'value' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Record a metric
     */
    public static function record(string $metric, int $value = 1, ?string $dimension = null, ?array $metadata = null, ?Carbon $date = null): void
    {
        $date = $date ?? now()->toDateString();
        
        $existing = self::where([
            'date' => $date,
            'metric' => $metric,
            'dimension' => $dimension,
        ])->first();

        if ($existing) {
            $existing->increment('value', $value);
            if ($metadata) {
                $existing->update(['metadata' => $metadata]);
            }
        } else {
            self::create([
                'date' => $date,
                'metric' => $metric,
                'dimension' => $dimension,
                'value' => $value,
                'metadata' => $metadata,
            ]);
        }
    }

    /**
     * Get metric value for a specific date
     */
    public static function getMetric(string $metric, ?string $dimension = null, ?Carbon $date = null): int
    {
        $date = $date ?? now()->toDateString();
        
        return self::where('date', $date)
                   ->where('metric', $metric)
                   ->when($dimension, fn($q) => $q->where('dimension', $dimension))
                   ->sum('value');
    }

    /**
     * Get metric trends over time
     */
    public static function getTrend(string $metric, int $days = 30, ?string $dimension = null): array
    {
        $startDate = now()->subDays($days)->toDateString();
        $endDate = now()->toDateString();
        
        return self::where('metric', $metric)
                   ->when($dimension, fn($q) => $q->where('dimension', $dimension))
                   ->whereBetween('date', [$startDate, $endDate])
                   ->orderBy('date')
                   ->pluck('value', 'date')
                   ->toArray();
    }

    /**
     * Get top performers for a metric
     */
    public static function getTopPerformers(string $metric, int $limit = 10, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();
        $endDate = now()->toDateString();
        
        return self::where('metric', $metric)
                   ->whereBetween('date', [$startDate, $endDate])
                   ->whereNotNull('dimension')
                   ->groupBy('dimension')
                   ->selectRaw('dimension, SUM(value) as total_value')
                   ->orderByDesc('total_value')
                   ->limit($limit)
                   ->pluck('total_value', 'dimension')
                   ->toArray();
    }

    /**
     * Common metrics
     */
    public const METRICS = [
        'user_registrations' => 'User Registrations',
        'user_logins' => 'User Logins',
        'messages_sent' => 'Messages Sent',
        'chats_created' => 'Chats Created',
        'credits_consumed' => 'Credits Consumed',
        'credits_purchased' => 'Credits Purchased',
        'revenue_generated' => 'Revenue Generated (cents)',
        'ai_assistant_usage' => 'AI Assistant Usage',
        'category_views' => 'Category Views',
        'api_requests' => 'API Requests',
    ];
}