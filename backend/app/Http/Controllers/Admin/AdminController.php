<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AIAssistant;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\CreditPackage;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    /**
     * Admin Dashboard - Overview statistics
     */
    public function dashboard()
    {
        try {
            $stats = Cache::remember('admin_dashboard_stats', 300, function () {
                $today = Carbon::today();
                $thisMonth = Carbon::now()->startOfMonth();
                $lastMonth = Carbon::now()->subMonth()->startOfMonth();

                return [
                    // User Statistics
                    'total_users' => User::count(),
                    'active_users' => User::where('is_active', true)->count(),
                    'verified_users' => User::whereNotNull('email_verified_at')->count(),
                    'new_users_today' => User::whereDate('created_at', $today)->count(),
                    'new_users_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                    
                    // AI Assistant Statistics
                    'total_ai_assistants' => AIAssistant::count(),
                    'active_ai_assistants' => AIAssistant::where('is_active', true)->count(),
                    'public_ai_assistants' => AIAssistant::where('is_public', true)->count(),
                    'new_ai_assistants_today' => AIAssistant::whereDate('created_at', $today)->count(),
                    
                    // Chat Statistics
                    'total_chats' => Chat::count(),
                    'active_chats' => Chat::where('is_archived', false)->count(),
                    'total_messages' => Message::count(),
                    'messages_today' => Message::whereDate('created_at', $today)->count(),
                    
                    // Revenue Statistics
                    'total_revenue' => Transaction::where('status', 'completed')->sum('price_cents') / 100,
                    'revenue_today' => Transaction::where('status', 'completed')
                        ->whereDate('created_at', $today)->sum('price_cents') / 100,
                    'revenue_this_month' => Transaction::where('status', 'completed')
                        ->where('created_at', '>=', $thisMonth)->sum('price_cents') / 100,
                    'pending_transactions' => Transaction::where('status', 'pending')->count(),
                    
                    // Credit Statistics
                    'total_credits_purchased' => Transaction::where('status', 'completed')->sum('credits_amount'),
                    'total_credits_consumed' => Message::sum('credits_consumed'),
                    'credits_consumed_today' => Message::whereDate('created_at', $today)->sum('credits_consumed'),
                    
                    // Growth Statistics
                    'user_growth_rate' => $this->calculateGrowthRate(
                        User::where('created_at', '>=', $thisMonth)->count(),
                        User::where('created_at', '>=', $lastMonth)
                            ->where('created_at', '<', $thisMonth)->count()
                    ),
                    'revenue_growth_rate' => $this->calculateGrowthRate(
                        Transaction::where('status', 'completed')
                            ->where('created_at', '>=', $thisMonth)->sum('price_cents'),
                        Transaction::where('status', 'completed')
                            ->where('created_at', '>=', $lastMonth)
                            ->where('created_at', '<', $thisMonth)->sum('price_cents')
                    ),
                ];
            });

            // Recent Activities
            $recent_users = User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']);
            $recent_transactions = Transaction::with('user')->latest()->limit(5)->get();
            $recent_ai_assistants = AIAssistant::with('user')->latest()->limit(5)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_users' => $recent_users,
                    'recent_transactions' => $recent_transactions,
                    'recent_ai_assistants' => $recent_ai_assistants,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analytics data for charts and reports
     */
    public function analytics(Request $request)
    {
        try {
            $period = $request->get('period', '30'); // days
            $startDate = Carbon::now()->subDays($period);

            $analytics = [
                // User Registration Chart
                'user_registrations' => User::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

                // Revenue Chart
                'daily_revenue' => Transaction::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(price_cents) / 100 as revenue')
                )
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

                // Message Activity Chart
                'message_activity' => Message::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(credits_consumed) as credits_used')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

                // Popular AI Assistants
                'popular_ai_assistants' => AIAssistant::select('name', 'usage_count', 'average_rating')
                ->orderBy('usage_count', 'desc')
                ->limit(10)
                ->get(),

                // Payment Method Distribution
                'payment_methods' => Transaction::select('payment_method', DB::raw('COUNT(*) as count'))
                ->where('status', 'completed')
                ->groupBy('payment_method')
                ->get(),

                // User Tier Distribution
                'user_tiers' => User::select('current_tier', DB::raw('COUNT(*) as count'))
                ->groupBy('current_tier')
                ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users with pagination and filtering
     */
    public function users(Request $request)
    {
        try {
            $query = User::query();

            // Apply filters
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('role')) {
                $query->where('role', $request->get('role'));
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('is_verified')) {
                if ($request->boolean('is_verified')) {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = min($request->get('per_page', 15), 100);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific user details
     */
    public function showUser(User $user)
    {
        try {
            $user->load(['aiAssistants', 'chats', 'transactions']);
            
            $userStats = [
                'total_messages' => Message::whereHas('chat', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->count(),
                'total_chats' => $user->chats()->count(),
                'total_ai_assistants' => $user->aiAssistants()->count(),
                'total_spent' => $user->transactions()->where('status', 'completed')->sum('price_cents') / 100,
                'credits_consumed_total' => Message::whereHas('chat', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->sum('credits_consumed'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'stats' => $userStats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user details
     */
    public function updateUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:user,admin,moderator',
            'is_active' => 'sometimes|boolean',
            'credits_balance' => 'sometimes|integer|min:0',
            'current_tier' => 'sometimes|integer|min:1|max:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update($request->only([
                'name', 'email', 'role', 'is_active', 'credits_balance', 'current_tier'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => ['user' => $user]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adjust user credits
     */
    public function adjustCredits(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer',
            'type' => 'required|in:add,subtract,set',
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $oldBalance = $user->credits_balance;
            $amount = $request->get('amount');
            $type = $request->get('type');

            switch ($type) {
                case 'add':
                    $user->addCredits($amount);
                    break;
                case 'subtract':
                    $user->deductCredits($amount);
                    break;
                case 'set':
                    $user->update(['credits_balance' => $amount]);
                    break;
            }

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'transaction_id' => 'admin_' . time(),
                'type' => 'admin_adjustment',
                'status' => 'completed',
                'payment_method' => 'admin',
                'credits_amount' => $type === 'set' ? $amount - $oldBalance : 
                                  ($type === 'add' ? $amount : -$amount),
                'price_cents' => 0,
                'currency' => 'USD',
                'notes' => $request->get('reason'),
                'processed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Credits adjusted successfully',
                'data' => [
                    'old_balance' => $oldBalance,
                    'new_balance' => $user->fresh()->credits_balance
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust credits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all transactions with filtering
     */
    public function transactions(Request $request)
    {
        try {
            $query = Transaction::with('user');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->get('payment_method'));
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->get('user_id'));
            }

            // Date range filter
            if ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->get('start_date'));
            }
            if ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->get('end_date'));
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = min($request->get('per_page', 15), 100);
            $transactions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test OpenAI connection
     */
    public function testOpenAI()
    {
        try {
            $openAIService = new OpenAIService();
            $result = $openAIService->testConnection();

            return response()->json([
                'success' => true,
                'message' => 'OpenAI connection test successful',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OpenAI connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system settings
     */
    public function settings()
    {
        try {
            $settings = Cache::remember('admin_settings', 1800, function () {
                return [
                    'app' => [
                        'name' => config('phoenix.app_name'),
                        'description' => config('phoenix.app_description'),
                        'url' => config('phoenix.app_url'),
                        'version' => config('phoenix.app_version'),
                    ],
                    'user' => [
                        'allow_registration' => config('phoenix.allow_registration'),
                        'require_email_verification' => config('phoenix.require_email_verification'),
                        'welcome_credits' => config('phoenix.welcome_credits'),
                    ],
                    'ai' => [
                        'default_model' => config('phoenix.openai.default_model'),
                        'max_tokens' => config('phoenix.openai.max_tokens'),
                        'temperature' => config('phoenix.openai.temperature'),
                    ],
                    'payments' => [
                        'currency' => config('phoenix.payments.currency'),
                        'stripe_enabled' => config('phoenix.payments.stripe.enabled'),
                        'paypal_enabled' => config('phoenix.payments.paypal.enabled'),
                        'bank_deposit_enabled' => config('phoenix.payments.bank_deposit.enabled'),
                    ],
                    'features' => config('phoenix.features'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        try {
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate growth rate percentage
     */
    private function calculateGrowthRate($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}