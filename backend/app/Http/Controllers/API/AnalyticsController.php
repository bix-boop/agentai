<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->except(['userStats']);
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get dashboard statistics (admin only)
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $stats = $this->analyticsService->getDashboardStats($days);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user growth trends
     */
    public function userGrowth(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $trends = $this->analyticsService->getUserGrowthTrends($days);

            return response()->json([
                'success' => true,
                'data' => $trends,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user growth trends',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get revenue trends
     */
    public function revenueTrends(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $trends = $this->analyticsService->getRevenueTrends($days);

            return response()->json([
                'success' => true,
                'data' => $trends,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load revenue trends',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get AI performance metrics
     */
    public function aiPerformance(): JsonResponse
    {
        try {
            $metrics = $this->analyticsService->getAIPerformanceMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load AI performance metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment method analytics
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $analytics = $this->analyticsService->getPaymentMethodAnalytics($days);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment method analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user engagement metrics
     */
    public function userEngagement(): JsonResponse
    {
        try {
            $metrics = $this->analyticsService->getUserEngagementMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user engagement metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversion funnel
     */
    public function conversionFunnel(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $funnel = $this->analyticsService->getConversionFunnel($days);

            return response()->json([
                'success' => true,
                'data' => $funnel,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversion funnel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time statistics
     */
    public function realTime(): JsonResponse
    {
        try {
            $stats = $this->analyticsService->getRealTimeStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load real-time statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate custom report
     */
    public function customReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metrics' => 'required|array|min:1',
            'metrics.*' => 'string|in:user_registrations,revenue,messages,chats,credits_consumed',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            $report = $this->analyticsService->generateCustomReport(
                $request->metrics,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate custom report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cost analysis
     */
    public function costAnalysis(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $analysis = $this->analyticsService->getCostAnalysis($days);

            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load cost analysis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's personal statistics
     */
    public function userStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $stats = [
                'credits_balance' => $user->credits_balance,
                'total_credits_purchased' => $user->total_credits_purchased,
                'total_credits_used' => $user->total_credits_used,
                'total_chats' => $user->chats()->count(),
                'active_chats' => $user->activeChats()->count(),
                'total_messages' => $user->chats()->withCount('messages')->get()->sum('messages_count'),
                'favorite_assistants' => $user->favoriteAiAssistants()->count(),
                'ratings_given' => $user->aiRatings()->count(),
                'member_since' => $user->created_at->toDateString(),
                'last_activity' => $user->last_login_at?->toDateString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}