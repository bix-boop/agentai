<?php

namespace App\Services;

use App\Models\AIAssistant;
use App\Models\User;
use App\Models\Analytics;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        
        if (!$this->apiKey) {
            throw new \Exception('OpenAI API key not configured');
        }
    }

    /**
     * Generate AI response for chat
     */
    public function generateResponse(AIAssistant $assistant, string $userMessage, array $conversationHistory = []): ?array
    {
        try {
            $startTime = microtime(true);

            // For testing: if API key is placeholder, return mock response
            if (!$this->apiKey || $this->apiKey === 'your_openai_api_key_here' || strpos($this->apiKey, 'your') === 0) {
                $endTime = microtime(true);
                $responseTime = round(($endTime - $startTime) * 1000, 2);
                
                return [
                    'content' => "This is a test response from {$assistant->name}. I'm here to help you with your request: \"{$userMessage}\". Please note that this is a mock response for testing purposes since no valid OpenAI API key is configured.",
                    'tokens_used' => 50,
                    'credits_consumed' => 1,
                    'model_used' => $assistant->model ?? 'gpt-3.5-turbo',
                    'processing_time' => $responseTime,
                ];
            }

            // Build messages array for OpenAI
            $messages = $this->buildMessagesArray($assistant, $userMessage, $conversationHistory);

            // Make API request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
                'model' => $assistant->model ?? 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => $assistant->temperature ?? 0.7,
                'frequency_penalty' => $assistant->frequency_penalty ?? 0,
                'presence_penalty' => $assistant->presence_penalty ?? 0,
                'max_tokens' => $assistant->max_tokens ?? 1000,
                'user' => 'user_' . auth()->id(),
            ]);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2); // milliseconds

            if (!$response->successful()) {
                Log::error('OpenAI API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'assistant_id' => $assistant->id,
                ]);
                return null;
            }

            $data = $response->json();
            
            if (!isset($data['choices'][0]['message']['content'])) {
                Log::error('Invalid OpenAI response structure', [
                    'response' => $data,
                    'assistant_id' => $assistant->id,
                ]);
                return null;
            }

            $content = $data['choices'][0]['message']['content'];
            $tokensUsed = $data['usage']['total_tokens'] ?? 0;

            // Apply content filtering
            $filteredContent = $this->filterContent($content, $assistant);

            // Record analytics
            Analytics::record('openai_requests');
            Analytics::record('tokens_consumed', $tokensUsed);

            Log::info('OpenAI response generated', [
                'assistant_id' => $assistant->id,
                'tokens_used' => $tokensUsed,
                'response_time' => $responseTime,
                'content_length' => strlen($filteredContent),
            ]);

            return [
                'content' => $filteredContent,
                'tokens_used' => $tokensUsed,
                'response_time' => $responseTime,
                'model' => $data['model'] ?? $assistant->model,
                'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI service error', [
                'assistant_id' => $assistant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate image using DALL-E
     */
    public function generateImage(User $user, string $prompt, string $size = '1024x1024', int $quantity = 1): ?array
    {
        try {
            $startTime = microtime(true);

            // Validate size
            $validSizes = ['256x256', '512x512', '1024x1024', '1792x1024', '1024x1792'];
            if (!in_array($size, $validSizes)) {
                $size = '1024x1024';
            }

            // Filter prompt for safety
            $filteredPrompt = $this->filterImagePrompt($prompt);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->baseUrl . '/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $filteredPrompt,
                'size' => $size,
                'quality' => 'standard',
                'n' => min($quantity, 1), // DALL-E 3 only supports 1 image
                'user' => 'user_' . $user->id,
            ]);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            if (!$response->successful()) {
                Log::error('OpenAI image generation failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'user_id' => $user->id,
                    'prompt' => $prompt,
                ]);
                return null;
            }

            $data = $response->json();
            
            if (!isset($data['data'][0]['url'])) {
                Log::error('Invalid OpenAI image response structure', [
                    'response' => $data,
                    'user_id' => $user->id,
                ]);
                return null;
            }

            $images = collect($data['data'])->map(function ($item) {
                return [
                    'url' => $item['url'],
                    'revised_prompt' => $item['revised_prompt'] ?? null,
                ];
            })->toArray();

            // Record analytics
            Analytics::record('images_generated', $quantity);
            Analytics::record('dalle_requests');

            Log::info('Image generated successfully', [
                'user_id' => $user->id,
                'prompt_length' => strlen($prompt),
                'response_time' => $responseTime,
                'image_count' => count($images),
            ]);

            return [
                'images' => $images,
                'prompt' => $filteredPrompt,
                'revised_prompt' => $data['data'][0]['revised_prompt'] ?? null,
                'response_time' => $responseTime,
            ];

        } catch (\Exception $e) {
            Log::error('Image generation error', [
                'user_id' => $user->id,
                'prompt' => $prompt,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get available models
     */
    public function getAvailableModels(): array
    {
        $cacheKey = 'openai_models';
        
        return Cache::remember($cacheKey, 3600, function () { // 1 hour cache
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->get($this->baseUrl . '/models');

                if ($response->successful()) {
                    $models = collect($response->json('data'))
                        ->filter(function ($model) {
                            return str_contains($model['id'], 'gpt');
                        })
                        ->pluck('id')
                        ->sort()
                        ->values()
                        ->toArray();

                    return $models;
                }

            } catch (\Exception $e) {
                Log::error('Failed to fetch OpenAI models', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Fallback to default models
            return [
                'gpt-3.5-turbo',
                'gpt-4',
                'gpt-4-turbo',
                'gpt-4o',
            ];
        });
    }

    /**
     * Test OpenAI API connection
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello, this is a test message.']
                ],
                'max_tokens' => 10,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'message' => 'OpenAI API connection successful',
                    'model' => $data['model'] ?? 'unknown',
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'OpenAI API connection failed',
                    'error' => $response->body(),
                    'status' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'OpenAI API connection error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Moderate content using OpenAI moderation
     */
    public function moderateContent(string $content): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/moderations', [
                'input' => $content,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $result = $data['results'][0] ?? [];

                return [
                    'flagged' => $result['flagged'] ?? false,
                    'categories' => $result['categories'] ?? [],
                    'category_scores' => $result['category_scores'] ?? [],
                ];
            }

        } catch (\Exception $e) {
            Log::error('Content moderation failed', [
                'error' => $e->getMessage(),
                'content_length' => strlen($content),
            ]);
        }

        // Return safe default if moderation fails
        return [
            'flagged' => false,
            'categories' => [],
            'category_scores' => [],
        ];
    }

    /**
     * Get token count for text
     */
    public function getTokenCount(string $text, string $model = 'gpt-3.5-turbo'): int
    {
        // Rough estimation: ~4 characters per token for most models
        // This is an approximation - for exact counts, you'd need tiktoken library
        return intval(strlen($text) / 4);
    }

    /**
     * Build messages array for OpenAI API
     */
    private function buildMessagesArray(AIAssistant $assistant, string $userMessage, array $conversationHistory): array
    {
        $messages = [];

        // Add system prompt
        if ($assistant->system_prompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->personalizeSystemPrompt($assistant->system_prompt, $assistant),
            ];
        }

        // Add conversation history
        foreach ($conversationHistory as $historyMessage) {
            $messages[] = $historyMessage;
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }

    /**
     * Personalize system prompt with assistant settings
     */
    private function personalizeSystemPrompt(string $systemPrompt, AIAssistant $assistant): string
    {
        $prompt = $systemPrompt;

        // Add language preference
        if (!empty($assistant->supported_languages)) {
            $languages = implode(', ', $assistant->supported_languages);
            $prompt .= "\n\nSupported languages: {$languages}";
        }

        // Add tone preferences
        if (!empty($assistant->response_tones)) {
            $tones = implode(', ', $assistant->response_tones);
            $prompt .= "\n\nResponse tones available: {$tones}";
        }

        // Add writing style preferences
        if (!empty($assistant->writing_styles)) {
            $styles = implode(', ', $assistant->writing_styles);
            $prompt .= "\n\nWriting styles available: {$styles}";
        }

        // Add expertise context
        if ($assistant->expertise) {
            $prompt .= "\n\nYour expertise: {$assistant->expertise}";
        }

        return $prompt;
    }

    /**
     * Filter AI response content
     */
    private function filterContent(string $content, AIAssistant $assistant): string
    {
        // Remove any blocked words
        if (!empty($assistant->blocked_words)) {
            foreach ($assistant->blocked_words as $blockedWord) {
                $content = str_ireplace($blockedWord, '[FILTERED]', $content);
            }
        }

        // Basic content safety
        $content = $this->removeUnsafeContent($content);

        return trim($content);
    }

    /**
     * Filter image generation prompt
     */
    private function filterImagePrompt(string $prompt): string
    {
        // Remove potentially harmful content
        $blockedTerms = [
            'nude', 'naked', 'sexual', 'violence', 'blood', 'gore',
            'weapon', 'drug', 'illegal', 'hate', 'racist', 'explicit'
        ];

        $filteredPrompt = $prompt;
        foreach ($blockedTerms as $term) {
            $filteredPrompt = str_ireplace($term, '', $filteredPrompt);
        }

        // Clean up extra spaces
        $filteredPrompt = preg_replace('/\s+/', ' ', $filteredPrompt);
        
        return trim($filteredPrompt);
    }

    /**
     * Remove unsafe content from AI responses
     */
    private function removeUnsafeContent(string $content): string
    {
        // Remove potential code injection attempts
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '[CODE_REMOVED]', $content);
        
        // Remove potential SQL injection patterns
        $content = preg_replace('/\b(DROP|DELETE|INSERT|UPDATE|SELECT)\s+/i', '[SQL_REMOVED] ', $content);
        
        // Remove excessive repetition (potential spam)
        $content = preg_replace('/(.{10,}?)\1{3,}/i', '$1[REPETITION_FILTERED]', $content);

        return $content;
    }

    /**
     * Calculate estimated cost for request
     */
    public function estimateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        // Pricing per 1K tokens (as of 2024)
        $pricing = [
            'gpt-3.5-turbo' => ['input' => 0.0015, 'output' => 0.002],
            'gpt-4' => ['input' => 0.03, 'output' => 0.06],
            'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
            'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
        ];

        $modelPricing = $pricing[$model] ?? $pricing['gpt-3.5-turbo'];
        
        $inputCost = ($inputTokens / 1000) * $modelPricing['input'];
        $outputCost = ($outputTokens / 1000) * $modelPricing['output'];
        
        return round($inputCost + $outputCost, 6);
    }

    /**
     * Check if API key is valid
     */
    public function validateApiKey(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)->get($this->baseUrl . '/models');

            return $response->successful();

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get API usage statistics
     */
    public function getUsageStats(int $days = 30): array
    {
        // This would require OpenAI usage API or internal tracking
        return [
            'total_requests' => Analytics::getMetric('openai_requests', null, now()->subDays($days)),
            'total_tokens' => Analytics::getMetric('tokens_consumed', null, now()->subDays($days)),
            'total_images' => Analytics::getMetric('images_generated', null, now()->subDays($days)),
            'estimated_cost' => $this->getEstimatedCost($days),
        ];
    }

    /**
     * Get estimated cost for period
     */
    private function getEstimatedCost(int $days): float
    {
        // This is a rough estimation based on token usage
        $totalTokens = Analytics::getMetric('tokens_consumed', null, now()->subDays($days));
        
        // Average cost estimation (mix of models)
        $avgCostPer1KTokens = 0.01; // USD
        
        return round(($totalTokens / 1000) * $avgCostPer1KTokens, 2);
    }

    /**
     * Stream chat completion (for real-time responses)
     */
    public function streamResponse(AIAssistant $assistant, string $userMessage, array $conversationHistory = []): \Generator
    {
        $messages = $this->buildMessagesArray($assistant, $userMessage, $conversationHistory);

        $postData = [
            'model' => $assistant->model ?? 'gpt-3.5-turbo',
            'messages' => $messages,
            'temperature' => $assistant->temperature ?? 0.7,
            'frequency_penalty' => $assistant->frequency_penalty ?? 0,
            'presence_penalty' => $assistant->presence_penalty ?? 0,
            'max_tokens' => $assistant->max_tokens ?? 1000,
            'stream' => true,
            'user' => 'user_' . auth()->id(),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/chat/completions',
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                if (strpos($data, 'data: ') === 0) {
                    $json = substr($data, 6);
                    if (trim($json) === '[DONE]') {
                        return strlen($data);
                    }
                    
                    $decoded = json_decode(trim($json), true);
                    if ($decoded && isset($decoded['choices'][0]['delta']['content'])) {
                        yield $decoded['choices'][0]['delta']['content'];
                    }
                }
                return strlen($data);
            },
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Analyze text sentiment
     */
    public function analyzeSentiment(string $text): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Analyze the sentiment of the following text and respond with only one word: positive, negative, or neutral.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'max_tokens' => 10,
                'temperature' => 0,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $sentiment = strtolower(trim($data['choices'][0]['message']['content'] ?? 'neutral'));
                
                return [
                    'sentiment' => $sentiment,
                    'confidence' => $this->getSentimentConfidence($sentiment),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Sentiment analysis failed', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);
        }

        return [
            'sentiment' => 'neutral',
            'confidence' => 0.5,
        ];
    }

    /**
     * Get sentiment confidence score
     */
    private function getSentimentConfidence(string $sentiment): float
    {
        // Simple confidence scoring based on keyword presence
        $confidenceMap = [
            'positive' => 0.8,
            'negative' => 0.8,
            'neutral' => 0.6,
        ];

        return $confidenceMap[$sentiment] ?? 0.5;
    }

    /**
     * Generate embeddings for text
     */
    public function generateEmbeddings(string $text): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/embeddings', [
                'model' => 'text-embedding-ada-002',
                'input' => $text,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['embedding'] ?? null;
            }

        } catch (\Exception $e) {
            Log::error('Embedding generation failed', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);
        }

        return null;
    }

    /**
     * Generate title for chat based on first message
     */
    public function generateChatTitle(string $firstMessage): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Generate a short, descriptive title (max 50 characters) for a chat conversation based on the user\'s first message. Respond with only the title, no quotes or extra text.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $firstMessage
                    ]
                ],
                'max_tokens' => 20,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $title = trim($data['choices'][0]['message']['content'] ?? '');
                
                // Clean up the title
                $title = str_replace(['"', "'", '`'], '', $title);
                $title = substr($title, 0, 50);
                
                return $title ?: 'New Chat';
            }

        } catch (\Exception $e) {
            Log::error('Chat title generation failed', [
                'error' => $e->getMessage(),
                'message_length' => strlen($firstMessage),
            ]);
        }

        // Fallback to truncated first message
        return substr($firstMessage, 0, 30) . (strlen($firstMessage) > 30 ? '...' : '');
    }
}