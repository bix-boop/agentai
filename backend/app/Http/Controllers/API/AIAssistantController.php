<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AIAssistant;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AIAssistantController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = AIAssistant::with(['category', 'user'])
                ->where('is_active', true)
                ->where('is_public', true);

            // Apply filters
            if ($request->has('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('expertise', 'like', "%{$search}%");
                });
            }

            $assistants = $query->orderBy('name')->paginate(12);

            return response()->json([
                'success' => true,
                'data' => $assistants
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch AI assistants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function popular()
    {
        try {
            $assistants = AIAssistant::with(['category'])
                ->where('is_active', true)
                ->where('is_public', true)
                ->orderBy('rating', 'desc')
                ->orderBy('total_chats', 'desc')
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assistants
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch popular assistants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function recent()
    {
        try {
            $assistants = AIAssistant::with(['category'])
                ->where('is_active', true)
                ->where('is_public', true)
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assistants
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent assistants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $assistants = AIAssistant::with(['category'])
                ->where('is_active', true)
                ->where('is_public', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('expertise', 'like', "%{$query}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assistants
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $assistant = AIAssistant::with(['category', 'user'])
                ->where('slug', $slug)
                ->where('is_active', true)
                ->where('is_public', true)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $assistant
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI assistant not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:500',
                'category_id' => 'required|exists:categories,id',
                'expertise' => 'required|string|max:255',
                'system_prompt' => 'required|string|max:2000',
                'welcome_message' => 'required|string|max:500',
                'temperature' => 'numeric|min:0|max:2',
                'max_tokens' => 'integer|min:100|max:4000',
                'model' => 'string|in:gpt-3.5-turbo,gpt-4,gpt-4-turbo-preview',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $assistant = AIAssistant::create(array_merge($validator->validated(), [
                'user_id' => Auth::id(),
                'slug' => \Str::slug($request->name),
                'is_public' => false, // Requires admin approval
                'is_active' => true,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'AI assistant created successfully',
                'data' => $assistant->load(['category'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create AI assistant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, AIAssistant $aiAssistant)
    {
        try {
            // Check ownership
            if ($aiAssistant->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'description' => 'string|max:500',
                'category_id' => 'exists:categories,id',
                'expertise' => 'string|max:255',
                'system_prompt' => 'string|max:2000',
                'welcome_message' => 'string|max:500',
                'temperature' => 'numeric|min:0|max:2',
                'max_tokens' => 'integer|min:100|max:4000',
                'model' => 'string|in:gpt-3.5-turbo,gpt-4,gpt-4-turbo-preview',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $aiAssistant->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'AI assistant updated successfully',
                'data' => $aiAssistant->load(['category'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update AI assistant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(AIAssistant $aiAssistant)
    {
        try {
            // Check ownership
            if ($aiAssistant->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $aiAssistant->delete();

            return response()->json([
                'success' => true,
                'message' => 'AI assistant deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete AI assistant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rate(Request $request, AIAssistant $aiAssistant)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // For now, just return success (implement rating system later)
            return response()->json([
                'success' => true,
                'message' => 'Rating submitted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function favorite(AIAssistant $aiAssistant)
    {
        try {
            // For now, just return success (implement favorites later)
            return response()->json([
                'success' => true,
                'message' => 'Added to favorites'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add to favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unfavorite(AIAssistant $aiAssistant)
    {
        try {
            // For now, just return success (implement favorites later)
            return response()->json([
                'success' => true,
                'message' => 'Removed from favorites'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}