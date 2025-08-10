<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Transaction;
use App\Models\AIAssistant;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get dashboard statistics for admin
     */
    public function getDashboardStats(int $days = 30): array
    {
        $cacheKey = "dashboard_stats_{$days}";
        
        return Cache::remember($cacheKey, 300, function () use ($days) { // 5 minutes cache
            $startDate = now()->subDays($days)->startOfDay();
            $endDate = now()->endOfDay();

            return [
                'overview' => $this->getOverviewStats($startDate, $endDate),
                'growth' => $this->getGrowthStats($startDate, $endDate),
                'revenue' => $this->getRevenueStats($startDate, $endDate),
                'engagement' => $this->getEngagementStats($startDate, $endDate),
                'ai_performance' => $this->getAIPerformanceStats($startDate, $endDate),
            ];
        });
    }

    /**
     * Get user growth trends
     */
    public function getUserGrowthTrends(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        // Daily registrations
        $dailyRegistrations = User::whereBetween('created_at', [$startDate, now()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as registrations')
            ->orderBy('date')
            ->get()
            ->pluck('registrations', 'date')
            ->toArray();

        // Fill missing dates with 0
        $trends = [];
        for ($date = $startDate->copy(); $date <= now(); $date->addDay()) {
            $dateStr = $date->toDateString();
            $trends[$dateStr] = $dailyRegistrations[$dateStr] ?? 0;
        }

        // Calculate growth rate
        $currentPeriod = array_sum(array_slice($trends, -7, 7)); // Last 7 days
        $previousPeriod = array_sum(array_slice($trends, -14, 7)); // Previous 7 days
        $growthRate = $previousPeriod > 0 ? (($currentPeriod - $previousPeriod) / $previousPeriod) * 100 : 0;

        return [
            'daily_trends' => $trends,
            'total_registrations' => array_sum($trends),
            'growth_rate' => round($growthRate, 2),
            'average_daily' => round(array_sum($trends) / count($trends), 1),
        ];
    }

    /**
     * Get revenue trends
     */
    public function getRevenueTrends(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        // Daily revenue
        $dailyRevenue = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, now()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, SUM(amount_cents) as revenue')
            ->orderBy('date')
            ->get()
            ->pluck('revenue', 'date')
            ->toArray();

        // Fill missing dates with 0
        $trends = [];
        for ($date = $startDate->copy(); $date <= now(); $date->addDay()) {
            $dateStr = $date->toDateString();
            $trends[$dateStr] = $dailyRevenue[$dateStr] ?? 0;
        }

        // Revenue by payment method
        $paymentMethodRevenue = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, now()])
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount_cents) as revenue, COUNT(*) as transactions')
            ->get()
            ->keyBy('payment_method');

        return [
            'daily_trends' => $trends,
            'total_revenue' => array_sum($trends),
            'payment_methods' => $paymentMethodRevenue,
            'average_daily' => round(array_sum($trends) / count($trends), 2),
        ];
    }

    /**
     * Get AI performance metrics
     */
    public function getAIPerformanceMetrics(): array
    {
        // Top performing AI assistants
        $topAssistants = AIAssistant::with('category:id,name')
            ->where('is_active', true)
            ->orderByDesc('usage_count')
            ->orderByDesc('average_rating')
            ->limit(10)
            ->get()
            ->map(function ($assistant) {
                return [
                    'id' => $assistant->id,
                    'name' => $assistant->name,
                    'category' => $assistant->category->name ?? 'Uncategorized',
                    'usage_count' => $assistant->usage_count,
                    'average_rating' => $assistant->average_rating,
                    'total_ratings' => $assistant->total_ratings,
                    'total_chats' => $assistant->chats()->count(),
                    'revenue_generated' => $assistant->chats()->sum('credits_used'),
                ];
            });

        // Category performance
        $categoryPerformance = Category::with(['aiAssistants' => function ($query) {
                $query->where('is_active', true);
            }])
            ->where('is_active', true)
            ->get()
            ->map(function ($category) {
                $assistants = $category->aiAssistants;
                
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_assistants' => $assistants->count(),
                    'total_usage' => $assistants->sum('usage_count'),
                    'average_rating' => $assistants->avg('average_rating'),
                    'total_chats' => Chat::whereIn('ai_assistant_id', $assistants->pluck('id'))->count(),
                ];
            })
            ->sortByDesc('total_usage')
            ->values();

        // Model usage statistics
        $modelUsage = AIAssistant::where('is_active', true)
            ->groupBy('model')
            ->selectRaw('model, COUNT(*) as assistant_count, SUM(usage_count) as total_usage')
            ->get()
            ->keyBy('model');

        return [
            'top_assistants' => $topAssistants,
            'category_performance' => $categoryPerformance,
            'model_usage' => $modelUsage,
        ];
    }

    /**
     * Get payment method analytics
     */
    public function getPaymentMethodAnalytics(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $paymentMethods = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method', 'status')
            ->selectRaw('payment_method, status, COUNT(*) as count, SUM(amount_cents) as revenue')
            ->get()
            ->groupBy('payment_method')
            ->map(function ($methods, $method) {
                $total = $methods->sum('count');
                $revenue = $methods->sum('revenue');
                $completed = $methods->where('status', 'completed')->sum('count');
                $pending = $methods->where('status', 'pending')->sum('count');
                $failed = $methods->where('status', 'failed')->sum('count');

                return [
                    'method' => $method,
                    'total_transactions' => $total,
                    'completed_transactions' => $completed,
                    'pending_transactions' => $pending,
                    'failed_transactions' => $failed,
                    'success_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                    'total_revenue' => $revenue,
                    'average_transaction' => $completed > 0 ? round($revenue / $completed, 2) : 0,
                ];
            });

        return $paymentMethods->toArray();
    }

    /**
     * Get user engagement metrics
     */
    public function getUserEngagementMetrics(): array
    {
        // Daily active users (last 30 days)
        $dailyActiveUsers = Chat::where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'), 'user_id')
            ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as active_users')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('active_users', 'date')
            ->toArray();

        // User retention analysis
        $newUsersLast30Days = User::where('created_at', '>=', now()->subDays(30))->pluck('id');
        $returningUsers = Chat::whereIn('user_id', $newUsersLast30Days)
            ->where('created_at', '>', DB::raw('(SELECT created_at FROM users WHERE users.id = chats.user_id)'))
            ->distinct('user_id')
            ->count();

        $retentionRate = $newUsersLast30Days->count() > 0 
            ? round(($returningUsers / $newUsersLast30Days->count()) * 100, 2) 
            : 0;

        // Average session metrics
        $avgChatsPerUser = Chat::selectRaw('user_id, COUNT(*) as chat_count')
            ->groupBy('user_id')
            ->get()
            ->avg('chat_count');

        $avgMessagesPerChat = Message::selectRaw('chat_id, COUNT(*) as message_count')
            ->groupBy('chat_id')
            ->get()
            ->avg('message_count');

        return [
            'daily_active_users' => $dailyActiveUsers,
            'retention_rate' => $retentionRate,
            'average_chats_per_user' => round($avgChatsPerUser, 1),
            'average_messages_per_chat' => round($avgMessagesPerChat, 1),
            'total_active_users' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        // Funnel stages
        $totalVisitors = Analytics::getMetric('page_views', null, null); // Would need to implement page tracking
        $registrations = User::whereBetween('created_at', [$startDate, now()])->count();
        $firstChatUsers = Chat::whereBetween('created_at', [$startDate, now()])
            ->distinct('user_id')
            ->count();
        $purchasingUsers = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, now()])
            ->distinct('user_id')
            ->count();
        $returningUsers = User::where('last_login_at', '>=', now()->subDays(7))
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        return [
            'visitors' => $totalVisitors ?: 1000, // Placeholder
            'registrations' => $registrations,
            'first_chat' => $firstChatUsers,
            'first_purchase' => $purchasingUsers,
            'returning_users' => $returningUsers,
            'conversion_rates' => [
                'visitor_to_registration' => $totalVisitors > 0 ? round(($registrations / $totalVisitors) * 100, 2) : 0,
                'registration_to_chat' => $registrations > 0 ? round(($firstChatUsers / $registrations) * 100, 2) : 0,
                'chat_to_purchase' => $firstChatUsers > 0 ? round(($purchasingUsers / $firstChatUsers) * 100, 2) : 0,
                'user_retention' => $registrations > 0 ? round(($returningUsers / $registrations) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Get real-time statistics
     */
    public function getRealTimeStats(): array
    {
        $today = now()->toDateString();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => [
                'new_users' => User::whereDate('created_at', $today)->count(),
                'total_chats' => Chat::whereDate('created_at', $today)->count(),
                'total_messages' => Message::whereDate('created_at', $today)->count(),
                'revenue' => Transaction::where('status', 'completed')
                    ->whereDate('created_at', $today)
                    ->sum('amount_cents'),
                'credits_consumed' => Analytics::getMetric('credits_consumed', null, Carbon::today()),
            ],
            'this_week' => [
                'new_users' => User::where('created_at', '>=', $thisWeek)->count(),
                'total_chats' => Chat::where('created_at', '>=', $thisWeek)->count(),
                'total_messages' => Message::where('created_at', '>=', $thisWeek)->count(),
                'revenue' => Transaction::where('status', 'completed')
                    ->where('created_at', '>=', $thisWeek)
                    ->sum('amount_cents'),
            ],
            'this_month' => [
                'new_users' => User::where('created_at', '>=', $thisMonth)->count(),
                'total_chats' => Chat::where('created_at', '>=', $thisMonth)->count(),
                'total_messages' => Message::where('created_at', '>=', $thisMonth)->count(),
                'revenue' => Transaction::where('status', 'completed')
                    ->where('created_at', '>=', $thisMonth)
                    ->sum('amount_cents'),
            ],
            'all_time' => [
                'total_users' => User::count(),
                'total_chats' => Chat::count(),
                'total_messages' => Message::count(),
                'total_revenue' => Transaction::where('status', 'completed')->sum('amount_cents'),
                'total_ai_assistants' => AIAssistant::where('is_active', true)->count(),
            ],
        ];
    }

    /**
     * Generate custom report
     */
    public function generateCustomReport(array $metrics, Carbon $startDate, Carbon $endDate): array
    {
        $report = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'metrics' => [],
        ];

        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'user_registrations':
                    $report['metrics']['user_registrations'] = $this->getUserRegistrationReport($startDate, $endDate);
                    break;
                
                case 'revenue':
                    $report['metrics']['revenue'] = $this->getRevenueReport($startDate, $endDate);
                    break;
                
                case 'messages':
                    $report['metrics']['messages'] = $this->getMessageReport($startDate, $endDate);
                    break;
                
                case 'chats':
                    $report['metrics']['chats'] = $this->getChatReport($startDate, $endDate);
                    break;
                
                case 'credits_consumed':
                    $report['metrics']['credits_consumed'] = $this->getCreditsReport($startDate, $endDate);
                    break;
            }
        }

        return $report;
    }

    /**
     * Get cost analysis
     */
    public function getCostAnalysis(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        // OpenAI API costs (estimated)
        $totalMessages = Message::whereBetween('created_at', [$startDate, $endDate])
            ->where('sender', 'assistant')
            ->sum(DB::raw('CHAR_LENGTH(content)'));

        $estimatedOpenAICost = $this->estimateOpenAICost($totalMessages);

        // Revenue vs costs
        $totalRevenue = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount_cents');

        $profit = $totalRevenue - $estimatedOpenAICost;
        $profitMargin = $totalRevenue > 0 ? round(($profit / $totalRevenue) * 100, 2) : 0;

        // Cost per user metrics
        $activeUsers = Chat::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'costs' => [
                'estimated_openai_cost' => $estimatedOpenAICost,
                'total_characters' => $totalMessages,
                'cost_per_character' => $totalMessages > 0 ? round($estimatedOpenAICost / $totalMessages, 6) : 0,
            ],
            'revenue' => [
                'total_revenue' => $totalRevenue,
                'profit' => $profit,
                'profit_margin' => $profitMargin,
            ],
            'efficiency' => [
                'cost_per_user' => $activeUsers > 0 ? round($estimatedOpenAICost / $activeUsers, 2) : 0,
                'revenue_per_user' => $activeUsers > 0 ? round($totalRevenue / $activeUsers, 2) : 0,
                'active_users' => $activeUsers,
            ],
        ];
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_users' => User::count(),
            'new_users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_chats' => Chat::count(),
            'new_chats' => Chat::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_messages' => Message::count(),
            'new_messages' => Message::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_revenue' => Transaction::where('status', 'completed')->sum('amount_cents'),
            'period_revenue' => Transaction::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount_cents'),
            'total_ai_assistants' => AIAssistant::where('is_active', true)->count(),
            'active_users_7d' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * Get growth statistics
     */
    private function getGrowthStats(Carbon $startDate, Carbon $endDate): array
    {
        $previousPeriod = $startDate->diffInDays($endDate) + 1;
        $previousStartDate = $startDate->copy()->subDays($previousPeriod);
        $previousEndDate = $startDate->copy()->subDay();

        $currentUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $previousUsers = User::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();

        $currentRevenue = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount_cents');
        $previousRevenue = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->sum('amount_cents');

        return [
            'user_growth' => [
                'current' => $currentUsers,
                'previous' => $previousUsers,
                'growth_rate' => $previousUsers > 0 ? round((($currentUsers - $previousUsers) / $previousUsers) * 100, 2) : 0,
            ],
            'revenue_growth' => [
                'current' => $currentRevenue,
                'previous' => $previousRevenue,
                'growth_rate' => $previousRevenue > 0 ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStats(Carbon $startDate, Carbon $endDate): array
    {
        $transactions = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_revenue' => $transactions->sum('amount_cents'),
            'total_transactions' => $transactions->count(),
            'average_transaction' => $transactions->avg('amount_cents'),
            'largest_transaction' => $transactions->max('amount_cents'),
            'credits_sold' => $transactions->sum('credits'),
        ];
    }

    /**
     * Get engagement statistics
     */
    private function getEngagementStats(Carbon $startDate, Carbon $endDate): array
    {
        $chats = Chat::whereBetween('created_at', [$startDate, $endDate]);
        $messages = Message::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_chats' => $chats->count(),
            'total_messages' => $messages->count(),
            'average_messages_per_chat' => $chats->count() > 0 
                ? round($messages->count() / $chats->count(), 1) 
                : 0,
            'active_users' => $chats->distinct('user_id')->count(),
            'chat_completion_rate' => $this->getChatCompletionRate($startDate, $endDate),
        ];
    }

    /**
     * Get AI performance statistics
     */
    private function getAIPerformanceStats(Carbon $startDate, Carbon $endDate): array
    {
        $aiUsage = Chat::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('ai_assistant_id')
            ->selectRaw('ai_assistant_id, COUNT(*) as chat_count')
            ->with('aiAssistant:id,name')
            ->orderByDesc('chat_count')
            ->limit(5)
            ->get();

        return [
            'most_used_assistants' => $aiUsage->map(function ($usage) {
                return [
                    'name' => $usage->aiAssistant->name ?? 'Unknown',
                    'chat_count' => $usage->chat_count,
                ];
            }),
            'total_ai_interactions' => Chat::whereBetween('created_at', [$startDate, $endDate])->count(),
        ];
    }

    /**
     * Get user registration report
     */
    private function getUserRegistrationReport(Carbon $startDate, Carbon $endDate): array
    {
        $dailyRegistrations = User::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as registrations')
            ->orderBy('date')
            ->pluck('registrations', 'date')
            ->toArray();

        return [
            'total_registrations' => array_sum($dailyRegistrations),
            'daily_breakdown' => $dailyRegistrations,
            'peak_day' => $dailyRegistrations ? array_keys($dailyRegistrations, max($dailyRegistrations))[0] : null,
            'average_daily' => count($dailyRegistrations) > 0 ? round(array_sum($dailyRegistrations) / count($dailyRegistrations), 1) : 0,
        ];
    }

    /**
     * Get revenue report
     */
    private function getRevenueReport(Carbon $startDate, Carbon $endDate): array
    {
        $dailyRevenue = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, SUM(amount_cents) as revenue')
            ->orderBy('date')
            ->pluck('revenue', 'date')
            ->toArray();

        return [
            'total_revenue' => array_sum($dailyRevenue),
            'daily_breakdown' => $dailyRevenue,
            'peak_day' => $dailyRevenue ? array_keys($dailyRevenue, max($dailyRevenue))[0] : null,
            'average_daily' => count($dailyRevenue) > 0 ? round(array_sum($dailyRevenue) / count($dailyRevenue), 2) : 0,
        ];
    }

    /**
     * Get message report
     */
    private function getMessageReport(Carbon $startDate, Carbon $endDate): array
    {
        $dailyMessages = Message::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as messages')
            ->orderBy('date')
            ->pluck('messages', 'date')
            ->toArray();

        return [
            'total_messages' => array_sum($dailyMessages),
            'daily_breakdown' => $dailyMessages,
            'peak_day' => $dailyMessages ? array_keys($dailyMessages, max($dailyMessages))[0] : null,
            'average_daily' => count($dailyMessages) > 0 ? round(array_sum($dailyMessages) / count($dailyMessages), 1) : 0,
        ];
    }

    /**
     * Get chat report
     */
    private function getChatReport(Carbon $startDate, Carbon $endDate): array
    {
        $dailyChats = Chat::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as chats')
            ->orderBy('date')
            ->pluck('chats', 'date')
            ->toArray();

        return [
            'total_chats' => array_sum($dailyChats),
            'daily_breakdown' => $dailyChats,
            'peak_day' => $dailyChats ? array_keys($dailyChats, max($dailyChats))[0] : null,
            'average_daily' => count($dailyChats) > 0 ? round(array_sum($dailyChats) / count($dailyChats), 1) : 0,
        ];
    }

    /**
     * Get credits report
     */
    private function getCreditsReport(Carbon $startDate, Carbon $endDate): array
    {
        $creditsConsumed = Analytics::getTrend('credits_consumed', $startDate->diffInDays($endDate) + 1);
        $creditsPurchased = Analytics::getTrend('credits_purchased', $startDate->diffInDays($endDate) + 1);

        return [
            'total_consumed' => array_sum($creditsConsumed),
            'total_purchased' => array_sum($creditsPurchased),
            'daily_consumption' => $creditsConsumed,
            'daily_purchases' => $creditsPurchased,
            'net_credits' => array_sum($creditsPurchased) - array_sum($creditsConsumed),
        ];
    }

    /**
     * Get chat completion rate
     */
    private function getChatCompletionRate(Carbon $startDate, Carbon $endDate): float
    {
        $totalChats = Chat::whereBetween('created_at', [$startDate, $endDate])->count();
        $chatsWithMessages = Chat::whereBetween('created_at', [$startDate, $endDate])
            ->has('messages', '>=', 2) // At least user message + AI response
            ->count();

        return $totalChats > 0 ? round(($chatsWithMessages / $totalChats) * 100, 2) : 0;
    }

    /**
     * Estimate OpenAI API cost
     */
    private function estimateOpenAICost(int $totalCharacters): int
    {
        // Rough estimation: $0.002 per 1K tokens, ~4 characters per token
        $estimatedTokens = $totalCharacters / 4;
        $costPer1KTokens = 0.002; // USD
        $estimatedCostUSD = ($estimatedTokens / 1000) * $costPer1KTokens;
        
        return intval($estimatedCostUSD * 100); // Convert to cents
    }

    /**
     * Record analytics event
     */
    public function recordEvent(string $event, array $data = []): void
    {
        Analytics::record($event, 1, null, $data);
    }

    /**
     * Get top performers for any metric
     */
    public function getTopPerformers(string $metric, int $limit = 10, int $days = 30): array
    {
        return Analytics::getTopPerformers($metric, $limit, $days);
    }

    /**
     * Get metric comparison between periods
     */
    public function compareMetrics(string $metric, int $currentDays = 30, int $previousDays = 30): array
    {
        $currentValue = Analytics::getMetric($metric, null, now()->subDays($currentDays));
        $previousValue = Analytics::getMetric($metric, null, now()->subDays($currentDays + $previousDays));

        $changePercent = $previousValue > 0 
            ? round((($currentValue - $previousValue) / $previousValue) * 100, 2) 
            : 0;

        return [
            'current_period' => $currentValue,
            'previous_period' => $previousValue,
            'change' => $currentValue - $previousValue,
            'change_percent' => $changePercent,
        ];
    }
}