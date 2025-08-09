<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Phoenix AI Platform Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Phoenix AI platform.
    | These settings control various aspects of the application behavior.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */
    'app_name' => env('PHOENIX_APP_NAME', 'Phoenix AI'),
    'app_description' => env('PHOENIX_APP_DESCRIPTION', 'Advanced AI Assistant Platform'),
    'app_version' => '1.0.0',
    'app_url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | User Registration & Authentication
    |--------------------------------------------------------------------------
    */
    'allow_registration' => env('PHOENIX_ALLOW_REGISTRATION', true),
    'require_email_verification' => env('PHOENIX_REQUIRE_EMAIL_VERIFICATION', true),
    'welcome_credits' => env('PHOENIX_WELCOME_CREDITS', 1000),
    'max_failed_login_attempts' => env('PHOENIX_MAX_FAILED_LOGIN_ATTEMPTS', 5),
    'login_lockout_minutes' => env('PHOENIX_LOGIN_LOCKOUT_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Credit System
    |--------------------------------------------------------------------------
    */
    'credit_per_character' => env('PHOENIX_CREDIT_PER_CHARACTER', 1),
    'credit_per_token' => env('PHOENIX_CREDIT_PER_TOKEN', 4), // Approximate 4 characters per token
    'image_generation_credits' => [
        '256x256' => env('PHOENIX_IMAGE_CREDITS_256', 1000),
        '512x512' => env('PHOENIX_IMAGE_CREDITS_512', 2000),
        '1024x1024' => env('PHOENIX_IMAGE_CREDITS_1024', 4000),
        '1792x1024' => env('PHOENIX_IMAGE_CREDITS_1792_1024', 6000),
        '1024x1792' => env('PHOENIX_IMAGE_CREDITS_1024_1792', 6000),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'default_model' => env('PHOENIX_DEFAULT_AI_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => env('PHOENIX_MAX_TOKENS', 2000),
        'temperature' => env('PHOENIX_DEFAULT_TEMPERATURE', 0.7),
        'frequency_penalty' => env('PHOENIX_DEFAULT_FREQUENCY_PENALTY', 0.0),
        'presence_penalty' => env('PHOENIX_DEFAULT_PRESENCE_PENALTY', 0.0),
        'timeout' => env('PHOENIX_OPENAI_TIMEOUT', 120), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Configuration
    |--------------------------------------------------------------------------
    */
    'chat' => [
        'max_message_length' => env('PHOENIX_MAX_MESSAGE_LENGTH', 5000),
        'min_message_length' => env('PHOENIX_MIN_MESSAGE_LENGTH', 1),
        'conversation_memory' => env('PHOENIX_CONVERSATION_MEMORY', 10),
        'max_free_messages' => env('PHOENIX_MAX_FREE_MESSAGES', 5),
        'enable_message_streaming' => env('PHOENIX_ENABLE_MESSAGE_STREAMING', true),
        'enable_voice_features' => env('PHOENIX_ENABLE_VOICE_FEATURES', true),
        'enable_image_generation' => env('PHOENIX_ENABLE_IMAGE_GENERATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Safety
    |--------------------------------------------------------------------------
    */
    'content_safety' => [
        'enable_content_filter' => env('PHOENIX_ENABLE_CONTENT_FILTER', true),
        'blocked_words' => [
            // Default blocked words - can be overridden in admin settings
        ],
        'auto_flag_threshold' => env('PHOENIX_AUTO_FLAG_THRESHOLD', 0.8),
        'enable_openai_moderation' => env('PHOENIX_ENABLE_OPENAI_MODERATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'currency' => env('PHOENIX_CURRENCY', 'USD'),
        'stripe' => [
            'enabled' => env('PHOENIX_STRIPE_ENABLED', true),
            'publishable_key' => env('STRIPE_KEY'),
            'secret_key' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'paypal' => [
            'enabled' => env('PHOENIX_PAYPAL_ENABLED', true),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'sandbox' => env('PAYPAL_SANDBOX', true),
        ],
        'bank_deposit' => [
            'enabled' => env('PHOENIX_BANK_DEPOSIT_ENABLED', true),
            'account_details' => env('PHOENIX_BANK_ACCOUNT_DETAILS', ''),
            'instructions' => env('PHOENIX_BANK_INSTRUCTIONS', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_avatar_size' => env('PHOENIX_MAX_AVATAR_SIZE', 2048), // KB
        'max_ai_avatar_size' => env('PHOENIX_MAX_AI_AVATAR_SIZE', 2048), // KB
        'max_package_image_size' => env('PHOENIX_MAX_PACKAGE_IMAGE_SIZE', 1024), // KB
        'allowed_avatar_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'avatar_dimensions' => [
            'width' => env('PHOENIX_AVATAR_WIDTH', 640),
            'height' => env('PHOENIX_AVATAR_HEIGHT', 640),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */
    'email' => [
        'from_name' => env('PHOENIX_MAIL_FROM_NAME', 'Phoenix AI'),
        'from_address' => env('PHOENIX_MAIL_FROM_ADDRESS', 'noreply@phoenixai.com'),
        'support_email' => env('PHOENIX_SUPPORT_EMAIL', 'support@phoenixai.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'api_requests_per_minute' => env('PHOENIX_API_RATE_LIMIT', 60),
        'chat_messages_per_minute' => env('PHOENIX_CHAT_RATE_LIMIT', 20),
        'image_generation_per_hour' => env('PHOENIX_IMAGE_RATE_LIMIT', 10),
        'registration_per_ip_per_hour' => env('PHOENIX_REGISTRATION_RATE_LIMIT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'ai_assistants_ttl' => env('PHOENIX_CACHE_AI_ASSISTANTS_TTL', 3600), // 1 hour
        'categories_ttl' => env('PHOENIX_CACHE_CATEGORIES_TTL', 7200), // 2 hours
        'settings_ttl' => env('PHOENIX_CACHE_SETTINGS_TTL', 1800), // 30 minutes
        'user_credits_ttl' => env('PHOENIX_CACHE_USER_CREDITS_TTL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | UI/UX Configuration
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'enable_dark_mode' => env('PHOENIX_ENABLE_DARK_MODE', true),
        'default_theme' => env('PHOENIX_DEFAULT_THEME', 'light'),
        'enable_animations' => env('PHOENIX_ENABLE_ANIMATIONS', true),
        'show_credit_balance' => env('PHOENIX_SHOW_CREDIT_BALANCE', true),
        'show_processing_time' => env('PHOENIX_SHOW_PROCESSING_TIME', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Configuration
    |--------------------------------------------------------------------------
    */
    'seo' => [
        'default_meta_title' => env('PHOENIX_META_TITLE', 'Phoenix AI - Advanced AI Assistant Platform'),
        'default_meta_description' => env('PHOENIX_META_DESCRIPTION', 'Experience the power of AI with Phoenix AI. Chat with intelligent assistants, generate images, and unlock premium features.'),
        'default_meta_keywords' => env('PHOENIX_META_KEYWORDS', 'AI, artificial intelligence, chatbot, assistant, OpenAI, GPT'),
        'enable_sitemap' => env('PHOENIX_ENABLE_SITEMAP', true),
        'enable_robots_txt' => env('PHOENIX_ENABLE_ROBOTS_TXT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),
        'facebook_pixel_id' => env('FACEBOOK_PIXEL_ID'),
        'enable_internal_analytics' => env('PHOENIX_ENABLE_INTERNAL_ANALYTICS', true),
        'track_user_behavior' => env('PHOENIX_TRACK_USER_BEHAVIOR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media Integration
    |--------------------------------------------------------------------------
    */
    'social' => [
        'enable_social_sharing' => env('PHOENIX_ENABLE_SOCIAL_SHARING', true),
        'facebook_url' => env('PHOENIX_FACEBOOK_URL', ''),
        'twitter_url' => env('PHOENIX_TWITTER_URL', ''),
        'linkedin_url' => env('PHOENIX_LINKEDIN_URL', ''),
        'instagram_url' => env('PHOENIX_INSTAGRAM_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    */
    'maintenance' => [
        'enabled' => env('PHOENIX_MAINTENANCE_MODE', false),
        'message' => env('PHOENIX_MAINTENANCE_MESSAGE', 'We are currently performing scheduled maintenance. Please check back soon.'),
        'allowed_ips' => explode(',', env('PHOENIX_MAINTENANCE_ALLOWED_IPS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'log_api_requests' => env('PHOENIX_LOG_API_REQUESTS', false),
        'log_chat_messages' => env('PHOENIX_LOG_CHAT_MESSAGES', true),
        'log_payments' => env('PHOENIX_LOG_PAYMENTS', true),
        'log_user_actions' => env('PHOENIX_LOG_USER_ACTIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'enable_query_caching' => env('PHOENIX_ENABLE_QUERY_CACHING', true),
        'enable_response_compression' => env('PHOENIX_ENABLE_RESPONSE_COMPRESSION', true),
        'enable_cdn' => env('PHOENIX_ENABLE_CDN', false),
        'cdn_url' => env('PHOENIX_CDN_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        'enable_csrf_protection' => env('PHOENIX_ENABLE_CSRF_PROTECTION', true),
        'enable_cors' => env('PHOENIX_ENABLE_CORS', true),
        'allowed_origins' => explode(',', env('PHOENIX_ALLOWED_ORIGINS', '*')),
        'enable_rate_limiting' => env('PHOENIX_ENABLE_RATE_LIMITING', true),
        'enable_ip_whitelisting' => env('PHOENIX_ENABLE_IP_WHITELISTING', false),
        'whitelisted_ips' => explode(',', env('PHOENIX_WHITELISTED_IPS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-party Integrations
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'google_cloud' => [
            'enabled' => env('PHOENIX_GOOGLE_CLOUD_ENABLED', false),
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        ],
        'pusher' => [
            'enabled' => env('PHOENIX_PUSHER_ENABLED', false),
            'app_id' => env('PUSHER_APP_ID'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'cluster' => env('PUSHER_APP_CLUSTER'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'enable_blog' => env('PHOENIX_ENABLE_BLOG', true),
        'enable_user_ai_creation' => env('PHOENIX_ENABLE_USER_AI_CREATION', true),
        'enable_public_ai_gallery' => env('PHOENIX_ENABLE_PUBLIC_AI_GALLERY', true),
        'enable_ai_ratings' => env('PHOENIX_ENABLE_AI_RATINGS', true),
        'enable_favorites' => env('PHOENIX_ENABLE_FAVORITES', true),
        'enable_user_profiles' => env('PHOENIX_ENABLE_USER_PROFILES', true),
        'enable_referral_system' => env('PHOENIX_ENABLE_REFERRAL_SYSTEM', false),
        'enable_affiliate_program' => env('PHOENIX_ENABLE_AFFILIATE_PROGRAM', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default AI Assistant Settings
    |--------------------------------------------------------------------------
    */
    'default_ai_settings' => [
        'temperature' => 0.7,
        'frequency_penalty' => 0.0,
        'presence_penalty' => 0.0,
        'max_tokens' => 2000,
        'model' => 'gpt-3.5-turbo',
        'min_message_length' => 1,
        'max_message_length' => 5000,
        'conversation_memory' => 10,
        'enable_voice' => true,
        'enable_image_generation' => true,
        'enable_web_search' => false,
        'content_filter_enabled' => true,
        'is_public' => true,
        'minimum_tier' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    */
    'supported_languages' => [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'zh' => 'Chinese (Simplified)',
        'zh-TW' => 'Chinese (Traditional)',
        'ar' => 'Arabic',
        'hi' => 'Hindi',
        'th' => 'Thai',
        'vi' => 'Vietnamese',
        'nl' => 'Dutch',
        'sv' => 'Swedish',
        'no' => 'Norwegian',
        'da' => 'Danish',
        'fi' => 'Finnish',
        'pl' => 'Polish',
        'cs' => 'Czech',
        'hu' => 'Hungarian',
        'ro' => 'Romanian',
        'bg' => 'Bulgarian',
        'hr' => 'Croatian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'et' => 'Estonian',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'mt' => 'Maltese',
        'el' => 'Greek',
        'tr' => 'Turkish',
        'he' => 'Hebrew',
        'ur' => 'Urdu',
        'fa' => 'Persian',
        'bn' => 'Bengali',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'ml' => 'Malayalam',
        'kn' => 'Kannada',
        'gu' => 'Gujarati',
        'mr' => 'Marathi',
        'ne' => 'Nepali',
        'si' => 'Sinhala',
        'my' => 'Myanmar',
        'km' => 'Khmer',
        'lo' => 'Lao',
        'ka' => 'Georgian',
        'am' => 'Amharic',
        'sw' => 'Swahili',
        'zu' => 'Zulu',
        'af' => 'Afrikaans',
        'is' => 'Icelandic',
        'ga' => 'Irish',
        'cy' => 'Welsh',
        'eu' => 'Basque',
        'ca' => 'Catalan',
        'gl' => 'Galician',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Tones
    |--------------------------------------------------------------------------
    */
    'response_tones' => [
        'formal' => 'Formal',
        'friendly' => 'Friendly',
        'casual' => 'Casual',
        'professional' => 'Professional',
        'educational' => 'Educational',
        'humorous' => 'Humorous',
        'empathetic' => 'Empathetic',
        'enthusiastic' => 'Enthusiastic',
    ],

    /*
    |--------------------------------------------------------------------------
    | Writing Styles
    |--------------------------------------------------------------------------
    */
    'writing_styles' => [
        'narrative' => 'Narrative',
        'descriptive' => 'Descriptive',
        'expository' => 'Expository',
        'argumentative' => 'Argumentative',
        'persuasive' => 'Persuasive',
        'journalistic' => 'Journalistic',
        'academic' => 'Academic',
        'creative' => 'Creative',
        'poetic' => 'Poetic',
        'technical' => 'Technical',
    ],
];