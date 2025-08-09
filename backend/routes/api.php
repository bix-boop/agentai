<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\AIAssistantController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\WebhookController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Status check
    Route::get('status', function () {
        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'version' => '1.0.0',
            'timestamp' => now(),
            'database' => 'connected'
        ]);
    });
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
    });

    // Public AI Assistants (for browsing)
    Route::prefix('ai-assistants')->group(function () {
        Route::get('/', [AIAssistantController::class, 'index']);
        Route::get('/popular', [AIAssistantController::class, 'popular']);
        Route::get('/recent', [AIAssistantController::class, 'recent']);
        Route::get('/search', [AIAssistantController::class, 'search']);
        Route::get('/{aiAssistant:slug}', [AIAssistantController::class, 'show']);
    });

    // Public Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{category:slug}', [CategoryController::class, 'show']);
        Route::get('/{category:slug}/ai-assistants', [CategoryController::class, 'aiAssistants']);
    });

    // System information
    Route::get('system/health', function () {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    });

    Route::get('system/version', function () {
        return response()->json(['version' => '1.0.0', 'name' => 'Phoenix AI']);
    });

    // Webhooks (no auth required but should be validated)
    Route::prefix('webhooks')->group(function () {
        Route::post('stripe', [WebhookController::class, 'stripe']);
        Route::post('paypal', [WebhookController::class, 'paypal']);
    });
});

// Authenticated routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Authentication (authenticated user actions)
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('resend-verification', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:6,1');
    });

    // User profile and account management
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::post('change-password', [UserController::class, 'changePassword']);
        Route::get('credits', [UserController::class, 'credits']);
        Route::get('transactions', [UserController::class, 'transactions']);
        Route::get('chats', [UserController::class, 'chats']);
        Route::delete('account', [UserController::class, 'deleteAccount']);
    });

    // AI Assistants (authenticated actions)
    Route::prefix('ai-assistants')->group(function () {
        Route::post('/', [AIAssistantController::class, 'store']);
        Route::put('/{aiAssistant}', [AIAssistantController::class, 'update']);
        Route::delete('/{aiAssistant}', [AIAssistantController::class, 'destroy']);
        Route::post('/{aiAssistant}/rate', [AIAssistantController::class, 'rate']);
        Route::post('/{aiAssistant}/favorite', [AIAssistantController::class, 'favorite']);
        Route::delete('/{aiAssistant}/favorite', [AIAssistantController::class, 'unfavorite']);
    });

    // Chat system
    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/', [ChatController::class, 'store']);
        Route::get('/{chat}', [ChatController::class, 'show']);
        Route::post('/{chat}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/{chat}/generate-image', [ChatController::class, 'generateImage']);
        Route::put('/{chat}/settings', [ChatController::class, 'updateSettings']);
        Route::post('/{chat}/archive', [ChatController::class, 'archive']);
        Route::post('/{chat}/restore', [ChatController::class, 'restore']);
        Route::delete('/{chat}', [ChatController::class, 'destroy']);
    });

    // Credit packages and payments
    Route::prefix('credit-packages')->group(function () {
        Route::get('/', [PaymentController::class, 'packages']);
        Route::get('/{creditPackage}', [PaymentController::class, 'showPackage']);
        Route::post('/{creditPackage}/purchase', [PaymentController::class, 'purchasePackage']);
    });

    // Payment processing
    Route::prefix('payments')->group(function () {
        // Stripe
        Route::post('stripe/create-intent', [PaymentController::class, 'createStripeIntent']);
        Route::post('stripe/confirm', [PaymentController::class, 'confirmStripePayment']);
        
        // PayPal
        Route::post('paypal/create-order', [PaymentController::class, 'createPayPalOrder']);
        Route::post('paypal/capture-order', [PaymentController::class, 'capturePayPalOrder']);
        
        // Bank deposit
        Route::post('bank-deposit', [PaymentController::class, 'bankDeposit']);
        
        // Payment history
        Route::get('history', [PaymentController::class, 'paymentHistory']);
    });
});

// Admin routes
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard
    Route::get('dashboard', [AdminController::class, 'dashboard']);
    Route::get('analytics', [AdminController::class, 'analytics']);

    // User management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminController::class, 'users']);
        Route::get('/{user}', [AdminController::class, 'showUser']);
        Route::put('/{user}', [AdminController::class, 'updateUser']);
        Route::post('/{user}/credits', [AdminController::class, 'adjustCredits']);
        Route::post('/{user}/disable', [AdminController::class, 'disableUser']);
        Route::post('/{user}/enable', [AdminController::class, 'enableUser']);
        Route::delete('/{user}', [AdminController::class, 'deleteUser']);
    });

    // AI Assistant management
    Route::prefix('ai-assistants')->group(function () {
        Route::get('/', [AdminController::class, 'aiAssistants']);
        Route::get('/{aiAssistant}', [AdminController::class, 'showAiAssistant']);
        Route::put('/{aiAssistant}', [AdminController::class, 'updateAiAssistant']);
        Route::post('/{aiAssistant}/approve', [AdminController::class, 'approveAiAssistant']);
        Route::post('/{aiAssistant}/reject', [AdminController::class, 'rejectAiAssistant']);
        Route::delete('/{aiAssistant}', [AdminController::class, 'deleteAiAssistant']);
    });

    // Transaction management
    Route::prefix('transactions')->group(function () {
        Route::get('/', [AdminController::class, 'transactions']);
        Route::get('/{transaction}', [AdminController::class, 'showTransaction']);
        Route::post('/{transaction}/approve', [AdminController::class, 'approveTransaction']);
        Route::post('/{transaction}/reject', [AdminController::class, 'rejectTransaction']);
        Route::post('/{transaction}/refund', [AdminController::class, 'refundTransaction']);
    });

    // Category management
    Route::prefix('categories')->group(function () {
        Route::get('/', [AdminController::class, 'categories']);
        Route::post('/', [AdminController::class, 'storeCategory']);
        Route::get('/{category}', [AdminController::class, 'showCategory']);
        Route::put('/{category}', [AdminController::class, 'updateCategory']);
        Route::delete('/{category}', [AdminController::class, 'deleteCategory']);
    });

    // Credit package management
    Route::prefix('credit-packages')->group(function () {
        Route::get('/', [AdminController::class, 'creditPackages']);
        Route::post('/', [AdminController::class, 'storeCreditPackage']);
        Route::get('/{creditPackage}', [AdminController::class, 'showCreditPackage']);
        Route::put('/{creditPackage}', [AdminController::class, 'updateCreditPackage']);
        Route::delete('/{creditPackage}', [AdminController::class, 'deleteCreditPackage']);
    });

    // System settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SettingsController::class, 'index']);
        Route::put('/', [App\Http\Controllers\Admin\SettingsController::class, 'update']);
        Route::post('test-openai', [App\Http\Controllers\Admin\SettingsController::class, 'testOpenAI']);
    });

    // Blog management
    Route::prefix('blog')->group(function () {
        Route::get('/', [AdminController::class, 'blogPosts']);
        Route::post('/', [AdminController::class, 'storeBlogPost']);
        Route::get('/{blogPost}', [AdminController::class, 'showBlogPost']);
        Route::put('/{blogPost}', [AdminController::class, 'updateBlogPost']);
        Route::delete('/{blogPost}', [AdminController::class, 'deleteBlogPost']);
    });

    // Content moderation
    Route::prefix('moderation')->group(function () {
        Route::get('flagged-messages', [AdminController::class, 'flaggedMessages']);
        Route::post('messages/{message}/approve', [AdminController::class, 'approveMessage']);
        Route::post('messages/{message}/reject', [AdminController::class, 'rejectMessage']);
        Route::get('reports', [AdminController::class, 'reports']);
    });

    // System maintenance
    Route::prefix('system')->group(function () {
        Route::post('maintenance-mode', [AdminController::class, 'enableMaintenanceMode']);
        Route::delete('maintenance-mode', [AdminController::class, 'disableMaintenanceMode']);
        Route::post('clear-cache', [AdminController::class, 'clearCache']);
        Route::get('logs', [AdminController::class, 'systemLogs']);
        Route::post('backup', [AdminController::class, 'createBackup']);
    });
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error_code' => 'endpoint_not_found'
    ], 404);
});