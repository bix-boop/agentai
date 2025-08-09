<?php

namespace App\Services;

use App\Models\AIAssistant;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private Client $client;
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('phoenix.openai.api_key');
        
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key not configured. Please add your API key in the admin settings.');
        }
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => config('phoenix.openai.timeout', 120),
        ]);
    }

    /**
     * Send a chat message to OpenAI
     */
    public function sendChatMessage(
        Chat $chat,
        string $userMessage,
        array $settings = []
    ): array {
        $assistant = $chat->aiAssistant;
        $user = $chat->user;

        // Build conversation context
        $messages = $this->buildConversationContext($chat, $userMessage, $settings);

        // Prepare request data
        $requestData = [
            'model' => $assistant->model,
            'messages' => $messages,
            'temperature' => $settings['temperature'] ?? $assistant->temperature,
            'frequency_penalty' => $settings['frequency_penalty'] ?? $assistant->frequency_penalty,
            'presence_penalty' => $settings['presence_penalty'] ?? $assistant->presence_penalty,
            'max_tokens' => $settings['max_tokens'] ?? $assistant->max_tokens,
            'user' => 'user_' . $user->id,
        ];

        try {
            $startTime = microtime(true);
            
            $response = $this->client->post('/chat/completions', [
                'json' => $requestData
            ]);

            $processingTime = microtime(true) - $startTime;
            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!isset($responseData['choices'][0]['message']['content'])) {
                throw new Exception('Invalid response from OpenAI API');
            }

            $aiResponse = $responseData['choices'][0]['message']['content'];
            $tokensUsed = $responseData['usage']['total_tokens'] ?? 0;
            $creditsUsed = $this->calculateCredits($aiResponse);

            // Check if user has enough credits
            if (!$user->hasCredits($creditsUsed)) {
                throw new Exception('Insufficient credits');
            }

            // Deduct credits from user
            $user->deductCredits($creditsUsed);

            // Save user message
            $userMessageModel = Message::create([
                'chat_id' => $chat->id,
                'role' => 'user',
                'content' => $userMessage,
                'credits_consumed' => 0,
                'tokens_used' => $responseData['usage']['prompt_tokens'] ?? 0,
                'model_used' => $assistant->model,
                'processing_time' => 0,
            ]);

            // Save AI response
            $aiMessageModel = Message::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $aiResponse,
                'credits_consumed' => $creditsUsed,
                'tokens_used' => $responseData['usage']['completion_tokens'] ?? 0,
                'model_used' => $assistant->model,
                'processing_time' => $processingTime,
                'metadata' => [
                    'openai_response' => $responseData,
                    'settings_used' => $settings,
                ],
            ]);

            // Update chat statistics
            $chat->addCreditsUsed($creditsUsed);
            $chat->incrementMessageCount();
            $chat->updateActivity();
            $chat->generateTitle();

            // Update assistant usage
            $assistant->incrementUsage();

            // Update user statistics
            $user->increment('total_messages', 2); // User + AI message
            if (!$user->first_chat_at) {
                $user->update(['first_chat_at' => now()]);
            }

            return [
                'success' => true,
                'response' => $aiResponse,
                'credits_used' => $creditsUsed,
                'tokens_used' => $tokensUsed,
                'processing_time' => $processingTime,
                'user_message' => $userMessageModel,
                'ai_message' => $aiMessageModel,
            ];

        } catch (GuzzleException $e) {
            Log::error('OpenAI API Error', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'error' => 'AI service temporarily unavailable. Please try again.',
                'error_code' => 'api_error',
            ];
        } catch (Exception $e) {
            Log::error('Chat Error', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'general_error',
            ];
        }
    }

    /**
     * Generate an image using DALL-E
     */
    public function generateImage(
        User $user,
        string $prompt,
        string $size = '1024x1024',
        int $n = 1
    ): array {
        $creditsRequired = $this->calculateImageCredits($size, $n);

        if (!$user->hasCredits($creditsRequired)) {
            return [
                'success' => false,
                'error' => 'Insufficient credits',
                'error_code' => 'insufficient_credits',
            ];
        }

        try {
            $response = $this->client->post('/images/generations', [
                'json' => [
                    'prompt' => $prompt,
                    'n' => $n,
                    'size' => $size,
                    'response_format' => 'url',
                    'user' => 'user_' . $user->id,
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!isset($responseData['data'])) {
                throw new Exception('Invalid response from OpenAI API');
            }

            // Deduct credits
            $user->deductCredits($creditsRequired);

            return [
                'success' => true,
                'images' => $responseData['data'],
                'credits_used' => $creditsRequired,
            ];

        } catch (Exception $e) {
            Log::error('Image Generation Error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'prompt' => $prompt,
            ]);

            return [
                'success' => false,
                'error' => 'Image generation failed. Please try again.',
                'error_code' => 'generation_error',
            ];
        }
    }

    /**
     * Build conversation context for OpenAI
     */
    private function buildConversationContext(Chat $chat, string $userMessage, array $settings): array
    {
        $assistant = $chat->aiAssistant;
        $messages = [];

        // Add system message with assistant's instructions
        $systemPrompt = $assistant->system_prompt;
        
        // Add language preference if specified
        if (!empty($settings['language'])) {
            $systemPrompt .= "\n\nPlease respond in " . $settings['language'] . ".";
        }

        // Add tone preference if specified
        if (!empty($settings['tone'])) {
            $systemPrompt .= "\n\nUse a " . $settings['tone'] . " tone in your responses.";
        }

        // Add writing style preference if specified
        if (!empty($settings['writing_style'])) {
            $systemPrompt .= "\n\nUse a " . $settings['writing_style'] . " writing style.";
        }

        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt
        ];

        // Add conversation history
        $recentMessages = $chat->recentMessages($assistant->conversation_memory)
                              ->get()
                              ->reverse();

        foreach ($recentMessages as $message) {
            if ($message->role !== 'system') {
                $messages[] = [
                    'role' => $message->role,
                    'content' => $message->content
                ];
            }
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        return $messages;
    }

    /**
     * Calculate credits based on response length
     */
    private function calculateCredits(string $response): int
    {
        // 1 credit per character (configurable)
        $creditPerCharacter = config('phoenix.credit_per_character', 1);
        return strlen($response) * $creditPerCharacter;
    }

    /**
     * Calculate credits for image generation
     */
    private function calculateImageCredits(string $size, int $count): int
    {
        $baseCredits = [
            '256x256' => 1000,
            '512x512' => 2000,
            '1024x1024' => 4000,
        ];

        return ($baseCredits[$size] ?? 2000) * $count;
    }

    /**
     * Test OpenAI API connection
     */
    public function testConnection(): array
    {
        try {
            $response = $this->client->get('/models');
            $responseData = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'models' => collect($responseData['data'] ?? [])->pluck('id')->toArray(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available models
     */
    public function getAvailableModels(): array
    {
        $connection = $this->testConnection();
        
        if (!$connection['success']) {
            return [];
        }

        return $connection['models'];
    }

    /**
     * Filter content for safety
     */
    public function filterContent(string $content, array $blockedWords = []): array
    {
        $isClean = true;
        $flaggedWords = [];

        foreach ($blockedWords as $word) {
            if (stripos($content, $word) !== false) {
                $isClean = false;
                $flaggedWords[] = $word;
            }
        }

        return [
            'is_clean' => $isClean,
            'flagged_words' => $flaggedWords,
            'content' => $isClean ? $content : '[Content filtered]',
        ];
    }
}