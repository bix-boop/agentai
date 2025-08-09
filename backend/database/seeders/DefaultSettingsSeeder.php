<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DefaultSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'Phoenix AI',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Site Name',
                'description' => 'The name of your Phoenix AI platform',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'site_description',
                'value' => 'Advanced AI Assistant Platform for Modern Businesses',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Site Description',
                'description' => 'Brief description of your platform',
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'default_language',
                'value' => 'en',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Default Language',
                'description' => 'Default language for new users',
                'is_public' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'default_currency',
                'value' => 'USD',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Default Currency',
                'description' => 'Default currency for payments',
                'is_public' => true,
                'sort_order' => 4,
            ],
            
            // AI Settings
            [
                'key' => 'default_ai_model',
                'value' => 'gpt-3.5-turbo',
                'type' => 'string',
                'group' => 'ai',
                'label' => 'Default AI Model',
                'description' => 'Default OpenAI model for new assistants',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'max_tokens',
                'value' => '2000',
                'type' => 'integer',
                'group' => 'ai',
                'label' => 'Max Tokens',
                'description' => 'Maximum tokens per AI response',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'temperature',
                'value' => '0.7',
                'type' => 'float',
                'group' => 'ai',
                'label' => 'Temperature',
                'description' => 'Default creativity level (0.0 to 2.0)',
                'is_public' => false,
                'sort_order' => 3,
            ],
            
            // Credit Settings
            [
                'key' => 'welcome_credits',
                'value' => '1000',
                'type' => 'integer',
                'group' => 'credits',
                'label' => 'Welcome Credits',
                'description' => 'Credits given to new users',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'credit_per_character',
                'value' => '1',
                'type' => 'integer',
                'group' => 'credits',
                'label' => 'Credits per Character',
                'description' => 'Credits consumed per character in AI responses',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'max_free_messages',
                'value' => '5',
                'type' => 'integer',
                'group' => 'credits',
                'label' => 'Max Free Messages',
                'description' => 'Maximum free messages for unregistered users',
                'is_public' => true,
                'sort_order' => 3,
            ],
            
            // Chat Settings
            [
                'key' => 'max_message_length',
                'value' => '4000',
                'type' => 'integer',
                'group' => 'chat',
                'label' => 'Max Message Length',
                'description' => 'Maximum characters per user message',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'enable_message_history',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'chat',
                'label' => 'Enable Message History',
                'description' => 'Allow users to view chat history',
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'require_email_verification',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'auth',
                'label' => 'Require Email Verification',
                'description' => 'Users must verify email before using the platform',
                'is_public' => false,
                'sort_order' => 1,
            ],
            
            // Security Settings
            [
                'key' => 'enable_content_filter',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Content Filter',
                'description' => 'Enable content filtering for inappropriate messages',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'max_requests_per_minute',
                'value' => '60',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Rate Limit',
                'description' => 'Maximum API requests per minute per user',
                'is_public' => false,
                'sort_order' => 2,
            ],
        ];

        foreach ($settings as $settingData) {
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                $settingData
            );
        }
    }
}