<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIAssistant;
use App\Models\Category;
use App\Models\User;

class AIAssistantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the system user (first admin user)
        $systemUser = User::where('role', 'admin')->first();
        if (!$systemUser) {
            $this->command->warn('No admin user found. Skipping AI assistant seeding.');
            return;
        }

        // Get categories
        $businessCategory = Category::where('slug', 'business-consulting')->first();
        $creativeCategory = Category::where('slug', 'creative-design')->first();
        $educationCategory = Category::where('slug', 'education-learning')->first();
        $techCategory = Category::where('slug', 'technology-programming')->first();
        $healthCategory = Category::where('slug', 'health-wellness')->first();

        $assistants = [
            [
                'name' => 'Business Advisor Pro',
                'slug' => 'business-advisor-pro',
                'description' => 'Expert business consultant with 20+ years of experience in strategy, operations, and growth. Specializes in startups, SMEs, and digital transformation.',
                'expertise' => 'Business Strategy & Operations',
                'welcome_message' => 'Hello! I\'m your Business Advisor Pro. I\'m here to help you with strategic planning, operations optimization, market analysis, and business growth strategies. What business challenge can I help you solve today?',
                'system_prompt' => 'You are a seasoned business consultant with over 20 years of experience. You provide strategic advice on business operations, market analysis, financial planning, and growth strategies. Always ask clarifying questions to understand the business context better. Provide actionable, practical advice with specific steps. Stay professional but approachable.',
                'category_id' => $businessCategory?->id,
                'user_id' => $systemUser->id,
                'temperature' => 0.7,
                'frequency_penalty' => 0.1,
                'presence_penalty' => 0.1,
                'max_tokens' => 1500,
                'model' => 'gpt-4',
                'enable_voice' => false,
                'enable_image_generation' => false,
                'enable_web_search' => true,
                'supported_languages' => ['en', 'es', 'fr', 'de'],
                'response_tones' => ['professional', 'friendly', 'formal'],
                'writing_styles' => ['professional', 'analytical', 'conversational'],
                'is_public' => true,
                'minimum_tier' => 1,
                'is_active' => true,
                'content_filter_enabled' => true,
            ],
            [
                'name' => 'Creative Writer',
                'slug' => 'creative-writer',
                'description' => 'Imaginative writing assistant for stories, poems, scripts, and creative content. Helps with character development, plot creation, and writing techniques.',
                'expertise' => 'Creative Writing & Storytelling',
                'welcome_message' => 'Greetings, fellow storyteller! I\'m here to help you craft amazing stories, develop compelling characters, and overcome writer\'s block. Whether you\'re writing a novel, short story, or screenplay, let\'s create something magical together!',
                'system_prompt' => 'You are a creative writing assistant with expertise in storytelling, character development, and various writing genres. You help users brainstorm ideas, develop plots, create characters, and improve their writing style. Be encouraging, imaginative, and provide specific, actionable writing advice. Ask questions to understand their creative vision.',
                'category_id' => $creativeCategory?->id,
                'user_id' => $systemUser->id,
                'temperature' => 1.2,
                'frequency_penalty' => 0.3,
                'presence_penalty' => 0.4,
                'max_tokens' => 2000,
                'model' => 'gpt-4',
                'enable_voice' => false,
                'enable_image_generation' => true,
                'enable_web_search' => false,
                'supported_languages' => ['en', 'es', 'fr', 'de', 'it'],
                'response_tones' => ['creative', 'inspiring', 'encouraging'],
                'writing_styles' => ['narrative', 'poetic', 'creative'],
                'is_public' => true,
                'minimum_tier' => 1,
                'is_active' => true,
                'content_filter_enabled' => true,
            ],
            [
                'name' => 'Code Mentor',
                'slug' => 'code-mentor',
                'description' => 'Expert programming tutor specializing in web development, algorithms, and software engineering best practices. Supports multiple programming languages.',
                'expertise' => 'Programming & Software Development',
                'welcome_message' => 'Hey there, developer! I\'m your Code Mentor, ready to help you with programming challenges, code reviews, debugging, and learning new technologies. What coding adventure are we tackling today?',
                'system_prompt' => 'You are an expert software engineer and programming tutor. You help with coding problems, explain programming concepts, review code, and teach best practices. Support multiple programming languages including JavaScript, Python, PHP, Java, and more. Provide clear explanations with code examples. Always encourage good coding practices and clean code principles.',
                'category_id' => $techCategory?->id,
                'user_id' => $systemUser->id,
                'temperature' => 0.3,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.1,
                'max_tokens' => 2000,
                'model' => 'gpt-4',
                'enable_voice' => false,
                'enable_image_generation' => false,
                'enable_web_search' => true,
                'supported_languages' => ['en'],
                'response_tones' => ['technical', 'educational', 'encouraging'],
                'writing_styles' => ['technical', 'educational', 'conversational'],
                'is_public' => true,
                'minimum_tier' => 1,
                'is_active' => true,
                'content_filter_enabled' => true,
            ],
            [
                'name' => 'Health & Wellness Coach',
                'slug' => 'health-wellness-coach',
                'description' => 'Certified wellness expert providing guidance on nutrition, fitness, mental health, and healthy lifestyle choices. Promotes evidence-based wellness practices.',
                'expertise' => 'Health, Nutrition & Wellness',
                'welcome_message' => 'Welcome to your wellness journey! I\'m here to support you with evidence-based advice on nutrition, fitness, mental health, and overall wellness. Remember, I provide general guidance - always consult healthcare professionals for medical concerns. How can I help you feel your best today?',
                'system_prompt' => 'You are a certified health and wellness coach with expertise in nutrition, fitness, mental health, and lifestyle optimization. Provide evidence-based advice and always remind users to consult healthcare professionals for medical concerns. Be supportive, motivating, and focus on sustainable, healthy practices. Never diagnose or treat medical conditions.',
                'category_id' => $healthCategory?->id,
                'user_id' => $systemUser->id,
                'temperature' => 0.8,
                'frequency_penalty' => 0.2,
                'presence_penalty' => 0.2,
                'max_tokens' => 1500,
                'model' => 'gpt-3.5-turbo',
                'enable_voice' => false,
                'enable_image_generation' => false,
                'enable_web_search' => true,
                'supported_languages' => ['en', 'es', 'fr'],
                'response_tones' => ['encouraging', 'professional', 'caring'],
                'writing_styles' => ['educational', 'motivational', 'conversational'],
                'is_public' => true,
                'minimum_tier' => 1,
                'is_active' => true,
                'content_filter_enabled' => true,
                'blocked_words' => ['medication', 'prescription', 'diagnosis', 'treatment'],
            ],
            [
                'name' => 'Math Tutor Elite',
                'slug' => 'math-tutor-elite',
                'description' => 'Advanced mathematics tutor covering algebra, calculus, statistics, and applied mathematics. Perfect for students and professionals needing math support.',
                'expertise' => 'Mathematics & Statistics',
                'welcome_message' => 'Hello, student! I\'m your Math Tutor Elite, ready to make mathematics clear and enjoyable. From basic algebra to advanced calculus, I\'ll help you understand concepts step-by-step. What math topic would you like to explore today?',
                'system_prompt' => 'You are an expert mathematics tutor with deep knowledge of algebra, geometry, calculus, statistics, and applied mathematics. Explain concepts clearly with step-by-step solutions. Use examples and analogies to make complex topics understandable. Always encourage students and help them build confidence in mathematics.',
                'category_id' => $educationCategory?->id,
                'user_id' => $systemUser->id,
                'temperature' => 0.2,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.1,
                'max_tokens' => 1800,
                'model' => 'gpt-4',
                'enable_voice' => false,
                'enable_image_generation' => true,
                'enable_web_search' => false,
                'supported_languages' => ['en', 'es', 'fr', 'de', 'zh'],
                'response_tones' => ['educational', 'patient', 'encouraging'],
                'writing_styles' => ['educational', 'step-by-step', 'clear'],
                'is_public' => true,
                'minimum_tier' => 1,
                'is_active' => true,
                'content_filter_enabled' => true,
            ],
            [
                'name' => 'Marketing Strategist Pro',
                'slug' => 'marketing-strategist-pro',
                'description' => 'Digital marketing expert specializing in SEO, social media, content marketing, and conversion optimization. Helps businesses grow their online presence.',
                'expertise' => 'Digital Marketing & Growth',
                'welcome_message' => 'Ready to supercharge your marketing? I\'m your Marketing Strategist Pro, here to help you create winning marketing campaigns, optimize conversions, and grow your brand online. What marketing challenge are we solving today?',
                'system_prompt' => 'You are a digital marketing expert with deep knowledge of SEO, social media marketing, content strategy, email marketing, and conversion optimization. Provide data-driven advice with actionable strategies. Stay current with marketing trends and best practices. Help users create effective marketing campaigns and measure their success.',
                'category_id' => $businessCategory?->id,
                'user_id' => $systemUser->id,
                'temperature' => 0.8,
                'frequency_penalty' => 0.2,
                'presence_penalty' => 0.2,
                'max_tokens' => 1500,
                'model' => 'gpt-4',
                'enable_voice' => false,
                'enable_image_generation' => true,
                'enable_web_search' => true,
                'supported_languages' => ['en', 'es', 'fr', 'de'],
                'response_tones' => ['professional', 'enthusiastic', 'strategic'],
                'writing_styles' => ['professional', 'persuasive', 'analytical'],
                'is_public' => true,
                'minimum_tier' => 2,
                'is_active' => true,
                'content_filter_enabled' => true,
            ],
        ];

        foreach ($assistants as $assistantData) {
            AIAssistant::updateOrCreate(
                ['slug' => $assistantData['slug']],
                $assistantData
            );
        }

        $this->command->info('AI assistants seeded successfully.');
    }
}