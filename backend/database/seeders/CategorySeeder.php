<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Business & Productivity',
                'slug' => 'business-productivity',
                'description' => 'AI assistants for business operations, productivity, and professional tasks',
                'icon' => 'briefcase',
                'color' => '#3B82F6',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Creative & Design',
                'slug' => 'creative-design',
                'description' => 'AI assistants for creative writing, design, and artistic endeavors',
                'icon' => 'paint-brush',
                'color' => '#8B5CF6',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Education & Learning',
                'slug' => 'education-learning',
                'description' => 'AI tutors and educational assistants for learning and skill development',
                'icon' => 'academic-cap',
                'color' => '#10B981',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Technology & Programming',
                'slug' => 'technology-programming',
                'description' => 'AI assistants for coding, debugging, and technical problem-solving',
                'icon' => 'code-bracket',
                'color' => '#F59E0B',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Health & Wellness',
                'slug' => 'health-wellness',
                'description' => 'AI assistants for health advice, fitness, and wellness guidance',
                'icon' => 'heart',
                'color' => '#EF4444',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Finance & Investment',
                'slug' => 'finance-investment',
                'description' => 'AI assistants for financial planning, investment advice, and money management',
                'icon' => 'currency-dollar',
                'color' => '#059669',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Customer Support',
                'slug' => 'customer-support',
                'description' => 'AI assistants specialized in customer service and support',
                'icon' => 'chat-bubble-left-right',
                'color' => '#DC2626',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Entertainment & Games',
                'slug' => 'entertainment-games',
                'description' => 'AI assistants for entertainment, gaming, and recreational activities',
                'icon' => 'puzzle-piece',
                'color' => '#7C3AED',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }
    }
}