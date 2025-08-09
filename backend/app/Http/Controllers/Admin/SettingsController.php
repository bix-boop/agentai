<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    /**
     * Display the settings page
     */
    public function index()
    {
        try {
            $settings = [
                // General Settings
                'site_name' => config('app.name', 'Phoenix AI'),
                'site_description' => 'Advanced AI Assistant Platform',
                'site_url' => config('app.url'),
                'default_language' => 'en',
                'default_currency' => 'USD',
                
                // AI Settings
                'openai_api_key' => config('phoenix.openai.api_key') ? '••••••••' : '',
                'openai_organization' => config('phoenix.openai.organization', ''),
                'default_ai_model' => config('phoenix.openai.default_model', 'gpt-3.5-turbo'),
                'max_tokens' => config('phoenix.openai.max_tokens', 2000),
                'temperature' => config('phoenix.openai.temperature', 0.7),
                
                // Credit Settings
                'welcome_credits' => config('phoenix.welcome_credits', 1000),
                'credit_per_character' => config('phoenix.credit_per_character', 1),
                'max_free_messages' => config('phoenix.chat.max_free_messages', 5),
                
                // Email Settings
                'mail_driver' => config('mail.default', 'smtp'),
                'mail_host' => config('mail.mailers.smtp.host', ''),
                'mail_port' => config('mail.mailers.smtp.port', 587),
                'mail_username' => config('mail.mailers.smtp.username', ''),
                'mail_encryption' => config('mail.mailers.smtp.encryption', 'tls'),
                'mail_from_address' => config('mail.from.address', ''),
                'mail_from_name' => config('mail.from.name', ''),
                
                // Payment Settings
                'stripe_publishable_key' => config('services.stripe.key') ? '••••••••' : '',
                'stripe_secret_key' => config('services.stripe.secret') ? '••••••••' : '',
                'paypal_client_id' => config('services.paypal.client_id') ? '••••••••' : '',
                'paypal_client_secret' => config('services.paypal.client_secret') ? '••••••••' : '',
                
                // Feature Flags
                'allow_registration' => config('phoenix.allow_registration', true),
                'require_email_verification' => config('phoenix.require_email_verification', true),
                'enable_image_generation' => config('phoenix.chat.enable_image_generation', true),
                'enable_voice_features' => config('phoenix.chat.enable_voice_features', true),
                'maintenance_mode' => config('phoenix.maintenance_mode', false),
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update system settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'sometimes|string|max:255',
            'site_description' => 'sometimes|string|max:500',
            'site_url' => 'sometimes|url',
            'openai_api_key' => 'sometimes|nullable|string|regex:/^sk-[a-zA-Z0-9_-]{20,}$/',
            'openai_organization' => 'sometimes|nullable|string',
            'default_ai_model' => 'sometimes|string|in:gpt-3.5-turbo,gpt-4,gpt-4-turbo-preview',
            'max_tokens' => 'sometimes|integer|min:100|max:4000',
            'temperature' => 'sometimes|numeric|min:0|max:2',
            'welcome_credits' => 'sometimes|integer|min:0',
            'credit_per_character' => 'sometimes|integer|min:1',
            'max_free_messages' => 'sometimes|integer|min:0',
            'stripe_publishable_key' => 'sometimes|nullable|string',
            'stripe_secret_key' => 'sometimes|nullable|string',
            'paypal_client_id' => 'sometimes|nullable|string',
            'paypal_client_secret' => 'sometimes|nullable|string',
            'mail_host' => 'sometimes|nullable|string',
            'mail_port' => 'sometimes|integer|min:1|max:65535',
            'mail_username' => 'sometimes|nullable|string',
            'mail_password' => 'sometimes|nullable|string',
            'mail_encryption' => 'sometimes|nullable|in:tls,ssl',
            'mail_from_address' => 'sometimes|nullable|email',
            'mail_from_name' => 'sometimes|nullable|string',
            'allow_registration' => 'sometimes|boolean',
            'require_email_verification' => 'sometimes|boolean',
            'enable_image_generation' => 'sometimes|boolean',
            'enable_voice_features' => 'sometimes|boolean',
            'maintenance_mode' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $envPath = base_path('.env');
            $envContent = File::get($envPath);
            $updated = false;

            // Update .env file with new values
            foreach ($request->all() as $key => $value) {
                if ($value === null || $value === '') continue;
                
                $updated = true;
                
                switch ($key) {
                    case 'site_name':
                        $envContent = $this->updateEnvValue($envContent, 'APP_NAME', $value);
                        break;
                    case 'site_url':
                        $envContent = $this->updateEnvValue($envContent, 'APP_URL', $value);
                        break;
                    case 'openai_api_key':
                        $envContent = $this->updateEnvValue($envContent, 'OPENAI_API_KEY', $value);
                        break;
                    case 'openai_organization':
                        $envContent = $this->updateEnvValue($envContent, 'OPENAI_ORGANIZATION', $value);
                        break;
                    case 'stripe_publishable_key':
                        $envContent = $this->updateEnvValue($envContent, 'STRIPE_KEY', $value);
                        break;
                    case 'stripe_secret_key':
                        $envContent = $this->updateEnvValue($envContent, 'STRIPE_SECRET', $value);
                        break;
                    case 'paypal_client_id':
                        $envContent = $this->updateEnvValue($envContent, 'PAYPAL_CLIENT_ID', $value);
                        break;
                    case 'paypal_client_secret':
                        $envContent = $this->updateEnvValue($envContent, 'PAYPAL_CLIENT_SECRET', $value);
                        break;
                    case 'mail_host':
                        $envContent = $this->updateEnvValue($envContent, 'MAIL_HOST', $value);
                        break;
                    case 'mail_port':
                        $envContent = $this->updateEnvValue($envContent, 'MAIL_PORT', $value);
                        break;
                    case 'mail_username':
                        $envContent = $this->updateEnvValue($envContent, 'MAIL_USERNAME', $value);
                        break;
                    case 'mail_password':
                        $envContent = $this->updateEnvValue($envContent, 'MAIL_PASSWORD', $value);
                        break;
                    case 'mail_encryption':
                        $envContent = $this->updateEnvValue($envContent, 'MAIL_ENCRYPTION', $value);
                        break;
                    case 'mail_from_address':
                        $envContent = $this->updateEnvValue($envContent, 'MAIL_FROM_ADDRESS', $value);
                        break;
                    case 'mail_from_name':
                        $envContent = $this->updateEnvValue($envContent, 'MAIL_FROM_NAME', $value);
                        break;
                }
            }

            if ($updated) {
                File::put($envPath, $envContent);
                
                // Clear config cache
                Cache::flush();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Settings updated successfully'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'No changes to update'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test OpenAI API connection
     */
    public function testOpenAI(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string|regex:/^sk-[a-zA-Z0-9_-]{20,}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key format',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKey = $request->get('api_key');
            
            // Test the API key by making a simple request
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                
                return response()->json([
                    'success' => true,
                    'message' => 'OpenAI API connection successful',
                    'data' => [
                        'models_available' => count($data['data'] ?? []),
                        'has_gpt4' => collect($data['data'] ?? [])->contains('id', 'gpt-4'),
                        'has_gpt35' => collect($data['data'] ?? [])->contains('id', 'gpt-3.5-turbo'),
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'OpenAI API connection failed'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OpenAI API test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a single environment variable
     */
    private function updateEnvValue($envContent, $key, $value)
    {
        $value = str_replace('"', '\\"', $value); // Escape quotes
        
        if (strpos($envContent, $key . '=') !== false) {
            // Update existing value
            $envContent = preg_replace(
                "/^{$key}=.*$/m",
                "{$key}=\"{$value}\"",
                $envContent
            );
        } else {
            // Add new value
            $envContent .= "\n{$key}=\"{$value}\"";
        }

        return $envContent;
    }
}