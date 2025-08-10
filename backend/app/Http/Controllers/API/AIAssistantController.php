<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AIAssistant;
use App\Models\Category;
use App\Models\UserFavorite;
use App\Models\AIRating;
use App\Models\Analytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AIAssistantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'public']);
    }

    /**
     * Get all AI assistants (public endpoint)
     */
    public function index(Request $request): JsonResponse
    {
        $query = AIAssistant::with(['category', 'user:id,name'])
            ->where('is_active', true)
            ->where('is_public', true);

        // Filter by category
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Search functionality
        if ($request->search) {
            $query->search($request->search);
        }

        // Sort options
        switch ($request->sort) {
            case 'rating':
                $query->orderByDesc('average_rating')->orderByDesc('total_ratings');
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'popular':
            default:
                $query->orderByDesc('usage_count');
                break;
        }

        $assistants = $query->paginate(12);

        // Add favorite status for authenticated users
        if (Auth::check()) {
            $userId = Auth::id();
            foreach ($assistants as $assistant) {
                $assistant->is_favorited = UserFavorite::isFavorited($userId, $assistant->id);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $assistants->items(),
            'pagination' => [
                'current_page' => $assistants->currentPage(),
                'last_page' => $assistants->lastPage(),
                'per_page' => $assistants->perPage(),
                'total' => $assistants->total(),
            ],
        ]);
    }

    /**
     * Get AI assistant details
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $assistant = AIAssistant::with(['category', 'user:id,name', 'publicRatings.user:id,name'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Check access permissions
        if (!$assistant->is_public && (!Auth::check() || !$assistant->canAccess(Auth::user()))) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this AI assistant',
            ], 403);
        }

        // Add user-specific data if authenticated
        if (Auth::check()) {
            $userId = Auth::id();
            $assistant->is_favorited = UserFavorite::isFavorited($userId, $assistant->id);
            $assistant->user_rating = AIRating::where('user_id', $userId)
                ->where('ai_assistant_id', $assistant->id)
                ->first();
        }

        // Add rating distribution
        $assistant->rating_distribution = $assistant->getRatingDistribution();

        return response()->json([
            'success' => true,
            'data' => $assistant,
        ]);
    }

    /**
     * Create new AI assistant (admin/creator only)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'expertise' => 'nullable|string|max:255',
            'welcome_message' => 'required|string|max:500',
            'system_prompt' => 'required|string|max:2000',
            'category_id' => 'nullable|exists:categories,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'temperature' => 'nullable|numeric|between:0,2',
            'frequency_penalty' => 'nullable|numeric|between:-2,2',
            'presence_penalty' => 'nullable|numeric|between:-2,2',
            'max_tokens' => 'nullable|integer|between:100,4000',
            'model' => 'nullable|string|in:gpt-3.5-turbo,gpt-4,gpt-4-turbo',
            'min_message_length' => 'nullable|integer|min:1',
            'max_message_length' => 'nullable|integer|max:2000',
            'conversation_memory' => 'nullable|integer|between:1,50',
            'enable_voice' => 'boolean',
            'enable_image_generation' => 'boolean',
            'enable_web_search' => 'boolean',
            'supported_languages' => 'nullable|array',
            'response_tones' => 'nullable|array',
            'writing_styles' => 'nullable|array',
            'is_public' => 'boolean',
            'required_packages' => 'nullable|array',
            'minimum_tier' => 'nullable|integer|min:1',
            'blocked_words' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['user_id'] = Auth::id();
            $data['slug'] = Str::slug($data['name']);

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('ai-assistants/avatars', 'public');
                $data['avatar'] = $avatarPath;
            }

            $assistant = AIAssistant::create($data);

            // Record analytics
            Analytics::record('ai_assistants_created');

            return response()->json([
                'success' => true,
                'message' => 'AI assistant created successfully',
                'data' => $assistant->load(['category', 'user:id,name']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create AI assistant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update AI assistant
     */
    public function update(Request $request, AIAssistant $assistant): JsonResponse
    {
        // Check permissions
        if (!Auth::user()->isAdmin() && $assistant->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own AI assistants',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'expertise' => 'nullable|string|max:255',
            'welcome_message' => 'sometimes|string|max:500',
            'system_prompt' => 'sometimes|string|max:2000',
            'category_id' => 'nullable|exists:categories,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'temperature' => 'nullable|numeric|between:0,2',
            'frequency_penalty' => 'nullable|numeric|between:-2,2',
            'presence_penalty' => 'nullable|numeric|between:-2,2',
            'max_tokens' => 'nullable|integer|between:100,4000',
            'model' => 'nullable|string|in:gpt-3.5-turbo,gpt-4,gpt-4-turbo',
            'min_message_length' => 'nullable|integer|min:1',
            'max_message_length' => 'nullable|integer|max:2000',
            'conversation_memory' => 'nullable|integer|between:1,50',
            'enable_voice' => 'boolean',
            'enable_image_generation' => 'boolean',
            'enable_web_search' => 'boolean',
            'supported_languages' => 'nullable|array',
            'response_tones' => 'nullable|array',
            'writing_styles' => 'nullable|array',
            'is_public' => 'boolean',
            'required_packages' => 'nullable|array',
            'minimum_tier' => 'nullable|integer|min:1',
            'blocked_words' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar
                if ($assistant->avatar) {
                    Storage::disk('public')->delete($assistant->avatar);
                }
                
                $avatarPath = $request->file('avatar')->store('ai-assistants/avatars', 'public');
                $data['avatar'] = $avatarPath;
            }

            // Update slug if name changed
            if (isset($data['name']) && $data['name'] !== $assistant->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $assistant->update($data);

            return response()->json([
                'success' => true,
                'message' => 'AI assistant updated successfully',
                'data' => $assistant->load(['category', 'user:id,name']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update AI assistant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete AI assistant
     */
    public function destroy(AIAssistant $assistant): JsonResponse
    {
        // Check permissions
        if (!Auth::user()->isAdmin() && $assistant->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own AI assistants',
            ], 403);
        }

        try {
            // Delete avatar file
            if ($assistant->avatar) {
                Storage::disk('public')->delete($assistant->avatar);
            }

            $assistant->delete();

            return response()->json([
                'success' => true,
                'message' => 'AI assistant deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete AI assistant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle favorite status
     */
    public function favorite(AIAssistant $aiAssistant): JsonResponse
    {
        try {
            $userId = Auth::id();
            $isFavorited = UserFavorite::toggle($userId, $aiAssistant->id);

            return response()->json([
                'success' => true,
                'message' => $isFavorited ? 'Added to favorites' : 'Removed from favorites',
                'data' => ['is_favorited' => $isFavorited],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle favorite',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function unfavorite(AIAssistant $aiAssistant): JsonResponse
    {
        try {
            $userId = Auth::id();
            if (UserFavorite::isFavorited($userId, $aiAssistant->id)) {
                UserFavorite::toggle($userId, $aiAssistant->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Removed from favorites',
                'data' => ['is_favorited' => false],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove favorite',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rate an AI assistant
     */
    public function rate(Request $request, AIAssistant $assistant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $userId = Auth::id();
            $data = $validator->validated();
            $data['user_id'] = $userId;
            $data['ai_assistant_id'] = $assistant->id;

            // Update or create rating
            $rating = AIRating::updateOrCreate(
                ['user_id' => $userId, 'ai_assistant_id' => $assistant->id],
                $data
            );

            // Update assistant rating statistics
            $assistant->updateRatingStats();

            return response()->json([
                'success' => true,
                'message' => 'Rating submitted successfully',
                'data' => $rating,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's AI assistants (for creators)
     */
    public function myAssistants(Request $request): JsonResponse
    {
        $assistants = AIAssistant::with(['category'])
            ->where('user_id', Auth::id())
            ->when($request->search, function ($query, $search) {
                $query->search($search);
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $assistants,
        ]);
    }

    /**
     * Get user's favorite AI assistants
     */
    public function favorites(): JsonResponse
    {
        $favorites = Auth::user()->favoriteAiAssistants()
            ->with(['category'])
            ->where('is_active', true)
            ->orderByDesc('user_favorites.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }

    /**
     * Get AI assistant statistics (admin only)
     */
    public function statistics(AIAssistant $assistant): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $stats = [
            'usage_count' => $assistant->usage_count,
            'total_chats' => $assistant->chats()->count(),
            'active_chats' => $assistant->chats()->where('is_archived', false)->count(),
            'total_messages' => $assistant->chats()->withCount('messages')->get()->sum('messages_count'),
            'average_rating' => $assistant->average_rating,
            'total_ratings' => $assistant->total_ratings,
            'rating_distribution' => $assistant->getRatingDistribution(),
            'favorites_count' => $assistant->favoritedBy()->count(),
            'revenue_generated' => $assistant->chats()->sum('credits_used'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Clone an AI assistant
     */
    public function clone(AIAssistant $assistant): JsonResponse
    {
        try {
            $clonedData = $assistant->toArray();
            
            // Remove unique fields and update for clone
            unset($clonedData['id'], $clonedData['created_at'], $clonedData['updated_at']);
            $clonedData['name'] = $assistant->name . ' (Copy)';
            $clonedData['slug'] = Str::slug($clonedData['name']);
            $clonedData['user_id'] = Auth::id();
            $clonedData['usage_count'] = 0;
            $clonedData['average_rating'] = 0;
            $clonedData['total_ratings'] = 0;

            // Copy avatar if exists
            if ($assistant->avatar) {
                $originalPath = $assistant->avatar;
                $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
                $newPath = 'ai-assistants/avatars/' . Str::random(40) . '.' . $extension;
                
                if (Storage::disk('public')->exists($originalPath)) {
                    Storage::disk('public')->copy($originalPath, $newPath);
                    $clonedData['avatar'] = $newPath;
                }
            }

            $clonedAssistant = AIAssistant::create($clonedData);

            return response()->json([
                'success' => true,
                'message' => 'AI assistant cloned successfully',
                'data' => $clonedAssistant->load(['category', 'user:id,name']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone AI assistant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available models and configurations
     */
    public function configurations(): JsonResponse
    {
        $assistant = new AIAssistant();
        
        return response()->json([
            'success' => true,
            'data' => [
                'models' => [
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast & Efficient)',
                    'gpt-4' => 'GPT-4 (Most Capable)',
                    'gpt-4-turbo' => 'GPT-4 Turbo (Fast & Advanced)',
                ],
                'languages' => $assistant->getDefaultLanguages(),
                'tones' => $assistant->getDefaultTones(),
                'styles' => $assistant->getDefaultStyles(),
                'categories' => Category::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'name', 'slug', 'color', 'icon']),
            ],
        ]);
    }

    /**
     * Bulk operations (admin only)
     */
    public function bulkAction(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:activate,deactivate,delete,make_public,make_private',
            'assistant_ids' => 'required|array|min:1',
            'assistant_ids.*' => 'exists:ai_assistants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $assistants = AIAssistant::whereIn('id', $request->assistant_ids);
            $count = $assistants->count();

            switch ($request->action) {
                case 'activate':
                    $assistants->update(['is_active' => true]);
                    $message = "Activated {$count} AI assistants";
                    break;
                
                case 'deactivate':
                    $assistants->update(['is_active' => false]);
                    $message = "Deactivated {$count} AI assistants";
                    break;
                
                case 'delete':
                    // Delete avatar files
                    foreach ($assistants->get() as $assistant) {
                        if ($assistant->avatar) {
                            Storage::disk('public')->delete($assistant->avatar);
                        }
                    }
                    $assistants->delete();
                    $message = "Deleted {$count} AI assistants";
                    break;
                
                case 'make_public':
                    $assistants->update(['is_public' => true]);
                    $message = "Made {$count} AI assistants public";
                    break;
                
                case 'make_private':
                    $assistants->update(['is_public' => false]);
                    $message = "Made {$count} AI assistants private";
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}