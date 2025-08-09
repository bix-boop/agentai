<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AIAssistant;
use App\Models\Category;
use App\Models\CreditPackage;
use App\Models\Transaction;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    public function dashboard()
    {
        try {
            $data = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'total_chats' => Chat::count(),
                'total_messages' => Message::count(),
                'total_ai_assistants' => AIAssistant::count(),
                'public_ai_assistants' => AIAssistant::where('is_public', true)->count(),
                'total_revenue' => Transaction::where('status', 'completed')->sum('price_cents') / 100,
                'pending_transactions' => Transaction::where('status', 'pending')->count(),
                'recent_users' => User::orderBy('created_at', 'desc')->limit(5)->get(),
                'recent_transactions' => Transaction::with(['user', 'creditPackage'])
                    ->orderBy('created_at', 'desc')->limit(5)->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function analytics()
    {
        try {
            $data = [
                'user_registrations' => $this->getUserRegistrationStats(),
                'revenue_stats' => $this->getRevenueStats(),
                'chat_activity' => $this->getChatActivityStats(),
                'popular_ai_assistants' => $this->getPopularAIStats(),
                'payment_methods' => $this->getPaymentMethodStats(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // User Management
    public function users(Request $request)
    {
        try {
            $query = User::query();
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }
            
            if ($request->has('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            $users = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showUser(User $user)
    {
        try {
            $userData = $user->load(['transactions', 'chats']);
            
            return response()->json([
                'success' => true,
                'data' => $userData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateUser(Request $request, User $user)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'email' => 'email|max:255|unique:users,email,' . $user->id,
                'role' => 'string|in:user,admin,moderator',
                'is_active' => 'boolean',
                'is_verified' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function adjustCredits(Request $request, User $user)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|integer',
                'reason' => 'required|string|max:255',
                'type' => 'required|string|in:add,subtract,set',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldBalance = $user->credits_balance;
            
            switch ($request->type) {
                case 'add':
                    $user->increment('credits_balance', $request->amount);
                    break;
                case 'subtract':
                    $user->decrement('credits_balance', $request->amount);
                    break;
                case 'set':
                    $user->update(['credits_balance' => $request->amount]);
                    break;
            }

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'admin_adjustment',
                'amount_cents' => 0,
                'currency' => 'USD',
                'credits' => $request->amount,
                'status' => 'completed',
                'description' => $request->reason,
                'payment_data' => [
                    'old_balance' => $oldBalance,
                    'new_balance' => $user->fresh()->credits_balance,
                    'admin_action' => $request->type,
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Credits adjusted successfully',
                'data' => [
                    'old_balance' => $oldBalance,
                    'new_balance' => $user->fresh()->credits_balance,
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

    public function disableUser(User $user)
    {
        try {
            $user->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'User disabled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to disable user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function enableUser(User $user)
    {
        try {
            $user->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'User enabled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to enable user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteUser(User $user)
    {
        try {
            // Prevent deleting self
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper methods for analytics
    private function getUserRegistrationStats()
    {
        return [
            'today' => User::whereDate('created_at', today())->count(),
            'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => User::whereMonth('created_at', now()->month)->count(),
            'total' => User::count(),
        ];
    }

    private function getRevenueStats()
    {
        return [
            'today' => Transaction::where('status', 'completed')->whereDate('created_at', today())->sum('amount_cents') / 100,
            'this_week' => Transaction::where('status', 'completed')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount_cents') / 100,
            'this_month' => Transaction::where('status', 'completed')->whereMonth('created_at', now()->month)->sum('amount_cents') / 100,
            'total' => Transaction::where('status', 'completed')->sum('amount_cents') / 100,
        ];
    }

    private function getChatActivityStats()
    {
        return [
            'today' => Chat::whereDate('created_at', today())->count(),
            'this_week' => Chat::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => Chat::whereMonth('created_at', now()->month)->count(),
            'total' => Chat::count(),
        ];
    }

    private function getPopularAIStats()
    {
        return AIAssistant::withCount('chats')
            ->orderBy('chats_count', 'desc')
            ->limit(10)
            ->get();
    }

    private function getPaymentMethodStats()
    {
        return Transaction::where('status', 'completed')
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount_cents) as total'))
            ->groupBy('payment_method')
            ->get();
    }

    // Placeholder methods for other admin functions
    public function aiAssistants() { return $this->placeholder('AI Assistants management'); }
    public function showAiAssistant() { return $this->placeholder('Show AI Assistant'); }
    public function updateAiAssistant() { return $this->placeholder('Update AI Assistant'); }
    public function approveAiAssistant() { return $this->placeholder('Approve AI Assistant'); }
    public function rejectAiAssistant() { return $this->placeholder('Reject AI Assistant'); }
    public function deleteAiAssistant() { return $this->placeholder('Delete AI Assistant'); }
    
    public function transactions() { return $this->placeholder('Transactions management'); }
    public function showTransaction() { return $this->placeholder('Show Transaction'); }
    public function approveTransaction() { return $this->placeholder('Approve Transaction'); }
    public function rejectTransaction() { return $this->placeholder('Reject Transaction'); }
    public function refundTransaction() { return $this->placeholder('Refund Transaction'); }
    
    public function categories() { return $this->placeholder('Categories management'); }
    public function storeCategory() { return $this->placeholder('Store Category'); }
    public function showCategory() { return $this->placeholder('Show Category'); }
    public function updateCategory() { return $this->placeholder('Update Category'); }
    public function deleteCategory() { return $this->placeholder('Delete Category'); }
    
    public function creditPackages() { return $this->placeholder('Credit Packages management'); }
    public function storeCreditPackage() { return $this->placeholder('Store Credit Package'); }
    public function showCreditPackage() { return $this->placeholder('Show Credit Package'); }
    public function updateCreditPackage() { return $this->placeholder('Update Credit Package'); }
    public function deleteCreditPackage() { return $this->placeholder('Delete Credit Package'); }
    
    public function blogPosts() { return $this->placeholder('Blog Posts management'); }
    public function storeBlogPost() { return $this->placeholder('Store Blog Post'); }
    public function showBlogPost() { return $this->placeholder('Show Blog Post'); }
    public function updateBlogPost() { return $this->placeholder('Update Blog Post'); }
    public function deleteBlogPost() { return $this->placeholder('Delete Blog Post'); }
    
    public function flaggedMessages() { return $this->placeholder('Flagged Messages'); }
    public function approveMessage() { return $this->placeholder('Approve Message'); }
    public function rejectMessage() { return $this->placeholder('Reject Message'); }
    public function reports() { return $this->placeholder('Reports'); }
    
    public function enableMaintenanceMode() { return $this->placeholder('Enable Maintenance Mode'); }
    public function disableMaintenanceMode() { return $this->placeholder('Disable Maintenance Mode'); }
    public function clearCache() {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
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
    public function systemLogs() { return $this->placeholder('System Logs'); }
    public function createBackup() { return $this->placeholder('Create Backup'); }

    private function placeholder($feature)
    {
        return response()->json([
            'success' => true,
            'message' => $feature . ' feature will be implemented in future updates',
            'data' => []
        ]);
    }
}