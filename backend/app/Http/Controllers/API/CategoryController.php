<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\AIAssistant;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $category = Category::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function aiAssistants($slug)
    {
        try {
            $category = Category::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            $assistants = AIAssistant::with(['category', 'user'])
                ->where('category_id', $category->id)
                ->where('is_active', true)
                ->where('is_public', true)
                ->orderBy('name')
                ->paginate(12);

            return response()->json([
                'success' => true,
                'data' => $assistants,
                'category' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch AI assistants for category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}