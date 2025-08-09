<?php

namespace Database\Seeders;

use App\Models\AIAssistant;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class AIAssistantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a system user for default assistants
        $systemUser = User::firstOrCreate(
            ['email' => 'system@phoenixai.local'],
            [
                'name' => 'Phoenix AI System',
                'password' => bcrypt('system_user_' . uniqid()),
                'role' => 'admin',
                'is_active' => true,
                'is_verified' => true,
                'credits_balance' => 999999,
            ]
        );

        $businessCategory = Category::where('slug', 'business-productivity')->first();
        $creativeCategory = Category::where('slug', 'creative-design')->first();
        $educationCategory = Category::where('slug', 'education-learning')->first();
        $techCategory = Category::where('slug', 'technology-programming')->first();

        $assistants = [
            [
                'user_id' => $systemUser->id,
                'category_id' => $businessCategory?->id,
                'name' => 'Business Advisor',
                'slug' => 'business-advisor',
                'description' => 'Expert business consultant and strategic advisor for entrepreneurs and business owners.',
                'expertise' => 'Business Strategy, Marketing, Operations, Financial Planning',
                'welcome_message' => 'Hello! I\'m your Business Advisor. I can help you with strategy, marketing, operations, financial planning, and growing your business. What business challenge can I help you solve today?',
                'system_prompt' => 'You are an expert business consultant with 20+ years of experience helping businesses of all sizes. Provide practical, actionable advice on strategy, marketing, operations, finance, and growth. Always ask clarifying questions to better understand the business context.',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'model' => 'gpt-3.5-turbo',
                'is_public' => true,
                'is_active' => true,
            ],
            [
                'user_id' => $systemUser->id,
                'category_id' => $creativeCategory?->id,
                'name' => 'Creative Writer',
                'slug' => 'creative-writer',
                'description' => 'Professional writer specializing in creative content, storytelling, and copywriting.',
                'expertise' => 'Creative Writing, Storytelling, Copywriting, Content Creation',
                'welcome_message' => 'Greetings, fellow creator! I\'m here to help you craft compelling stories, engaging copy, and creative content. Whether you need help with a novel, blog post, or marketing copy, let\'s bring your ideas to life!',
                'system_prompt' => 'You are a professional creative writer with expertise in storytelling, copywriting, and content creation. Help users develop engaging narratives, improve their writing style, and create compelling content. Be encouraging and provide specific, actionable feedback.',
                'temperature' => 0.9,
                'max_tokens' => 2500,
                'model' => 'gpt-3.5-turbo',
                'is_public' => true,
                'is_active' => true,
            ],
            [
                'user_id' => $systemUser->id,
                'category_id' => $educationCategory?->id,
                'name' => 'Learning Tutor',
                'slug' => 'learning-tutor',
                'description' => 'Patient and knowledgeable tutor for various subjects and skill development.',
                'expertise' => 'Education, Tutoring, Skill Development, Academic Support',
                'welcome_message' => 'Hello, student! I\'m your personal Learning Tutor. I can help you understand complex topics, develop new skills, and achieve your learning goals. What would you like to learn today?',
                'system_prompt' => 'You are a patient and knowledgeable tutor who adapts to different learning styles. Break down complex topics into digestible parts, provide examples, and encourage questions. Always check for understanding and offer practice opportunities.',
                'temperature' => 0.6,
                'max_tokens' => 2000,
                'model' => 'gpt-3.5-turbo',
                'is_public' => true,
                'is_active' => true,
            ],
            [
                'user_id' => $systemUser->id,
                'category_id' => $techCategory?->id,
                'name' => 'Code Assistant',
                'slug' => 'code-assistant',
                'description' => 'Expert programming assistant for coding, debugging, and software development.',
                'expertise' => 'Programming, Debugging, Code Review, Software Architecture',
                'welcome_message' => 'Hey developer! I\'m your Code Assistant, ready to help with programming challenges, debugging, code reviews, and architecture decisions. What coding problem can I help you solve?',
                'system_prompt' => 'You are an expert software developer with deep knowledge of multiple programming languages, frameworks, and best practices. Help users write clean, efficient code, debug issues, and make good architectural decisions. Always explain your reasoning and suggest best practices.',
                'temperature' => 0.3,
                'max_tokens' => 3000,
                'model' => 'gpt-4',
                'is_public' => true,
                'is_active' => true,
            ],
        ];

        foreach ($assistants as $assistantData) {
            AIAssistant::updateOrCreate(
                ['slug' => $assistantData['slug']],
                $assistantData
            );
        }
    }
}