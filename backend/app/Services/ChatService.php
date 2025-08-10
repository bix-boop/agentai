<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use App\Models\AIAssistant;
use App\Models\User;
use App\Models\Analytics;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatService
{
    private OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Create a new chat
     */
    public function createChat(User $user, AIAssistant $aiAssistant, array $settings = []): Chat
    {
        // Check if user can access this AI assistant
        if (!$aiAssistant->canAccess($user)) {
            throw new Exception('You do not have access to this AI assistant');
        }

        $chat = Chat::create([
            'user_id' => $user->id,
            'ai_assistant_id' => $aiAssistant->id,
            'title' => null, // Will be auto-generated from first message
            'settings' => array_merge([
                'language' => 'en',
                'tone' => 'friendly',
                'style' => 'conversational',
            ], $settings),
            'last_activity_at' => now(),
        ]);

        // Send welcome message
        if ($aiAssistant->welcome_message) {
            $this->addMessage($chat, 'assistant', $aiAssistant->welcome_message, [
                'is_welcome' => true,
                'credits_consumed' => 0,
            ]);
        }

        // Record analytics
        Analytics::record('chats_created');
        $aiAssistant->recordUsage($user);

        return $chat;
    }

    /**
     * Send a message in a chat
     */
    public function sendMessage(Chat $chat, string $content, array $options = []): Message
    {
        $user = $chat->user;
        $aiAssistant = $chat->aiAssistant;

        // Validate message length
        $contentLength = strlen($content);
        if ($contentLength < $aiAssistant->min_message_length) {
            throw new Exception("Message too short. Minimum {$aiAssistant->min_message_length} characters required.");
        }
        if ($contentLength > $aiAssistant->max_message_length) {
            throw new Exception("Message too long. Maximum {$aiAssistant->max_message_length} characters allowed.");
        }

        // Check if user has enough credits
        $estimatedCredits = $this->estimateCredits($content, $aiAssistant);
        if ($user->credits_balance < $estimatedCredits) {
            throw new Exception('Insufficient credits. Please purchase more credits to continue.');
        }

        // Content filtering
        if ($aiAssistant->content_filter_enabled) {
            $filteredContent = $this->filterContent($content, $aiAssistant);
            if ($filteredContent !== $content) {
                throw new Exception('Message contains inappropriate content and has been blocked.');
            }
        }

        // Add user message
        $userMessage = $this->addMessage($chat, 'user', $content);

        try {
            // Generate AI response
            $response = $this->generateAIResponse($chat, $aiAssistant, $options);
            
            // Add AI message
            $aiMessage = $this->addMessage($chat, 'assistant', $response['content'], [
                'tokens_used' => $response['tokens_used'],
                'model_used' => $response['model'],
                'processing_time' => $response['processing_time'],
                'credits_consumed' => $response['credits_consumed'],
            ]);

            // Deduct credits from user
            $user->deductCredits($response['credits_consumed']);

            // Update chat statistics
            $chat->increment('message_count', 2); // User + AI message
            $chat->increment('credits_used', $response['credits_consumed']);
            $chat->update(['last_activity_at' => now()]);

            // Auto-generate title if this is the first user message
            if ($chat->message_count <= 3 && !$chat->title) {
                $chat->update(['title' => $this->generateChatTitle($content)]);
            }

            // Record analytics
            Analytics::record('messages_sent', 2); // User + AI
            Analytics::record('credits_consumed', $response['credits_consumed']);

            return $aiMessage;

        } catch (Exception $e) {
            Log::error('Chat message failed', [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'ai_assistant_id' => $aiAssistant->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to generate AI response: ' . $e->getMessage());
        }
    }

    /**
     * Generate AI response using OpenAI
     */
    private function generateAIResponse(Chat $chat, AIAssistant $aiAssistant, array $options = []): array
    {
        $startTime = microtime(true);

        // Get conversation history
        $messages = $this->buildConversationHistory($chat, $aiAssistant);

        // Prepare OpenAI request
        $requestData = [
            'model' => $aiAssistant->model,
            'messages' => $messages,
            'temperature' => $aiAssistant->temperature,
            'frequency_penalty' => $aiAssistant->frequency_penalty,
            'presence_penalty' => $aiAssistant->presence_penalty,
            'max_tokens' => $aiAssistant->max_tokens,
        ];

        // Add language and tone instructions if specified
        $chatSettings = $chat->settings ?? [];
        if (!empty($chatSettings['language']) && $chatSettings['language'] !== 'en') {
            $requestData['messages'][] = [
                'role' => 'system',
                'content' => "Please respond in " . $this->getLanguageName($chatSettings['language']) . "."
            ];
        }

        if (!empty($chatSettings['tone'])) {
            $requestData['messages'][] = [
                'role' => 'system',
                'content' => "Use a " . $chatSettings['tone'] . " tone in your response."
            ];
        }

        if (!empty($chatSettings['style'])) {
            $requestData['messages'][] = [
                'role' => 'system',
                'content' => "Write in a " . $chatSettings['style'] . " style."
            ];
        }

        // Call OpenAI API
        $response = $this->openAIService->createChatCompletion($requestData);

        $processingTime = microtime(true) - $startTime;
        $content = $response['choices'][0]['message']['content'];
        $tokensUsed = $response['usage']['total_tokens'];
        
        // Calculate credits consumed (1 credit per character)
        $creditsConsumed = strlen($content);

        return [
            'content' => $content,
            'tokens_used' => $tokensUsed,
            'model' => $aiAssistant->model,
            'processing_time' => round($processingTime, 3),
            'credits_consumed' => $creditsConsumed,
        ];
    }

    /**
     * Build conversation history for OpenAI
     */
    private function buildConversationHistory(Chat $chat, AIAssistant $aiAssistant): array
    {
        $messages = [];

        // Add system prompt
        $messages[] = [
            'role' => 'system',
            'content' => $aiAssistant->system_prompt
        ];

        // Get recent messages based on conversation memory
        $recentMessages = $chat->messages()
            ->where('role', '!=', 'system')
            ->orderBy('created_at', 'desc')
            ->limit($aiAssistant->conversation_memory * 2) // User + AI pairs
            ->get()
            ->reverse();

        foreach ($recentMessages as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content
            ];
        }

        return $messages;
    }

    /**
     * Add a message to the chat
     */
    private function addMessage(Chat $chat, string $role, string $content, array $metadata = []): Message
    {
        return Message::create([
            'chat_id' => $chat->id,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'credits_consumed' => $metadata['credits_consumed'] ?? 0,
            'tokens_used' => $metadata['tokens_used'] ?? null,
            'model_used' => $metadata['model_used'] ?? null,
            'processing_time' => $metadata['processing_time'] ?? null,
        ]);
    }

    /**
     * Estimate credits needed for a message
     */
    private function estimateCredits(string $content, AIAssistant $aiAssistant): int
    {
        // Rough estimation: input length + expected output length
        $inputLength = strlen($content);
        $expectedOutputLength = min($aiAssistant->max_tokens * 4, 2000); // Rough character estimate
        
        return $inputLength + $expectedOutputLength;
    }

    /**
     * Filter content for inappropriate words
     */
    private function filterContent(string $content, AIAssistant $aiAssistant): string
    {
        $blockedWords = $aiAssistant->blocked_words ?? [];
        
        // Add default blocked words
        $defaultBlockedWords = [
            'spam', 'scam', 'hack', 'illegal', 'drugs', 'violence'
        ];
        
        $allBlockedWords = array_merge($blockedWords, $defaultBlockedWords);
        
        foreach ($allBlockedWords as $word) {
            if (stripos($content, $word) !== false) {
                return ''; // Return empty to indicate blocked content
            }
        }
        
        return $content;
    }

    /**
     * Generate chat title from first message
     */
    private function generateChatTitle(string $content): string
    {
        $title = substr($content, 0, 50);
        if (strlen($content) > 50) {
            $title .= '...';
        }
        return $title;
    }

    /**
     * Get language name from code
     */
    private function getLanguageName(string $code): string
    {
        $languages = [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
        ];

        return $languages[$code] ?? 'English';
    }

    /**
     * Generate image using DALL-E
     */
    public function generateImage(Chat $chat, string $prompt): array
    {
        $user = $chat->user;
        $aiAssistant = $chat->aiAssistant;

        if (!$aiAssistant->enable_image_generation) {
            throw new Exception('Image generation is not enabled for this AI assistant');
        }

        // Check credits (image generation costs more)
        $imageCredits = 1000; // Cost for image generation
        if ($user->credits_balance < $imageCredits) {
            throw new Exception('Insufficient credits for image generation. Need ' . $imageCredits . ' credits.');
        }

        try {
            $response = $this->openAIService->generateImage($prompt);
            
            // Deduct credits
            $user->deductCredits($imageCredits);
            
            // Record the image generation
            $this->addMessage($chat, 'assistant', "🎨 Generated image: {$prompt}", [
                'type' => 'image',
                'image_url' => $response['url'],
                'credits_consumed' => $imageCredits,
            ]);

            // Update chat stats
            $chat->increment('credits_used', $imageCredits);
            $chat->update(['last_activity_at' => now()]);

            // Record analytics
            Analytics::record('images_generated');
            Analytics::record('credits_consumed', $imageCredits);

            return $response;

        } catch (Exception $e) {
            Log::error('Image generation failed', [
                'chat_id' => $chat->id,
                'prompt' => $prompt,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to generate image: ' . $e->getMessage());
        }
    }

    /**
     * Archive a chat
     */
    public function archiveChat(Chat $chat): void
    {
        $chat->update(['is_archived' => true]);
    }

    /**
     * Restore a chat
     */
    public function restoreChat(Chat $chat): void
    {
        $chat->update(['is_archived' => false]);
    }

    /**
     * Delete a chat and all its messages
     */
    public function deleteChat(Chat $chat): void
    {
        $chat->delete(); // Cascade will delete messages
    }

    /**
     * Get chat statistics
     */
    public function getChatStats(Chat $chat): array
    {
        return [
            'total_messages' => $chat->message_count,
            'credits_used' => $chat->credits_used,
            'duration' => $chat->created_at->diffForHumans($chat->last_activity_at),
            'ai_assistant' => $chat->aiAssistant->name,
            'category' => $chat->aiAssistant->category->name ?? 'Uncategorized',
        ];
    }
}