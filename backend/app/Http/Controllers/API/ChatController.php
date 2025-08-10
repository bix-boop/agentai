<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\AIAssistant;
use App\Models\Analytics;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    private OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->middleware('auth:sanctum');
        $this->openAIService = $openAIService;
    }

    /**
     * Get user's chats
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Auth::user()->chats()
                ->with(['aiAssistant:id,name,avatar,slug', 'messages' => function ($q) {
                    $q->latest()->limit(1); // Get latest message for preview
                }]);

            // Filter options
            if ($request->has('archived')) {
                $query->where('is_archived', $request->boolean('archived'));
            } else {
                $query->where('is_archived', false); // Default to active chats
            }

            if ($request->has('ai_assistant_id')) {
                $query->where('ai_assistant_id', $request->ai_assistant_id);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhereHas('messages', function ($mq) use ($search) {
                          $mq->where('content', 'like', "%{$search}%");
                      });
                });
            }

            $chats = $query->orderByDesc('updated_at')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $chats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new chat
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ai_assistant_id' => 'required|exists:ai_assistants,id',
            'title' => 'nullable|string|max:255',
            'initial_message' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $assistant = AIAssistant::findOrFail($request->ai_assistant_id);

            // Check access permissions
            if (!$assistant->canAccess($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this AI assistant',
                ], 403);
            }

            DB::beginTransaction();

            // Create chat
            $chat = Chat::create([
                'user_id' => $user->id,
                'ai_assistant_id' => $assistant->id,
                'title' => $request->title ?: "Chat with {$assistant->name}",
                'is_archived' => false,
                'credits_used' => 0,
            ]);

            // Send welcome message from AI
            $welcomeMessage = Message::create([
                'chat_id' => $chat->id,
                'sender' => 'assistant',
                'content' => $assistant->welcome_message,
                'tokens_used' => 0,
                'credits_consumed' => 0,
            ]);

            // Send initial user message if provided
            if ($request->initial_message) {
                $userMessage = Message::create([
                    'chat_id' => $chat->id,
                    'sender' => 'user',
                    'content' => $request->initial_message,
                    'tokens_used' => 0,
                    'credits_consumed' => 0,
                ]);

                // Generate AI response
                $aiResponse = $this->generateAIResponse($chat, $request->initial_message);
                if ($aiResponse) {
                    $chat->increment('credits_used', $aiResponse['credits_consumed']);
                    $user->deductCredits($aiResponse['credits_consumed']);
                }
            }

            // Update assistant usage
            $assistant->recordUsage($user);

            // Record analytics
            Analytics::record('chats_created');
            Analytics::record('ai_assistant_usage', 1, (string)$assistant->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Chat created successfully',
                'data' => $chat->load(['aiAssistant:id,name,avatar,slug', 'messages']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific chat with messages
     */
    public function show(Chat $chat): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        try {
            $chat->load([
                'aiAssistant:id,name,avatar,slug,expertise,model,temperature',
                'messages' => function ($query) {
                    $query->orderBy('created_at');
                }
            ]);

            // Update last activity
            $chat->touch();

            return response()->json([
                'success' => true,
                'data' => $chat,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send message in chat
     */
    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:2000',
            'type' => 'nullable|string|in:text,image',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $content = $request->content;
            $type = $request->type ?? 'text';

            // Check message length limits
            $assistant = $chat->aiAssistant;
            if (strlen($content) < ($assistant->min_message_length ?? 1)) {
                return response()->json([
                    'success' => false,
                    'message' => "Message must be at least {$assistant->min_message_length} characters",
                ], 422);
            }

            if (strlen($content) > ($assistant->max_message_length ?? 2000)) {
                return response()->json([
                    'success' => false,
                    'message' => "Message must not exceed {$assistant->max_message_length} characters",
                ], 422);
            }

            // Check for blocked words
            if ($this->containsBlockedWords($content, $assistant->blocked_words ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message contains inappropriate content',
                ], 422);
            }

            DB::beginTransaction();

            // Create user message
            $userMessage = Message::create([
                'chat_id' => $chat->id,
                'role' => 'user',
                'content' => $content,
                'metadata' => [
                    'type' => $type,
                ],
                'tokens_used' => 0,
                'credits_consumed' => 0,
            ]);

            // Generate AI response
            $aiResponse = $this->generateAIResponse($chat, $content);
            
            if (!$aiResponse) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate AI response',
                ], 500);
            }

            // Check if user has enough credits
            if (!$user->hasCredits($aiResponse['credits_consumed'])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient credits',
                    'data' => [
                        'required_credits' => $aiResponse['credits_consumed'],
                        'current_balance' => $user->credits_balance,
                    ],
                ], 402); // Payment Required
            }

            // Deduct credits from user
            $user->deductCredits($aiResponse['credits_consumed']);

            // Update chat stats
            $chat->increment('credits_used', $aiResponse['credits_consumed']);
            $chat->touch(); // Update last activity

            // Record analytics
            Analytics::record('messages_sent');
            Analytics::record('credits_consumed', $aiResponse['credits_consumed'], (string)$user->id);
            Analytics::record('ai_assistant_usage', 1, (string)$assistant->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'user_message' => $userMessage,
                    'ai_message' => $aiResponse['message'],
                    'credits_used' => $aiResponse['credits_consumed'],
                    'user_credits_remaining' => $user->fresh()->credits_balance,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update chat (rename, archive, etc.)
     */
    public function update(Request $request, Chat $chat): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'is_archived' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $chat->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Chat updated successfully',
                'data' => $chat,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete chat
     */
    public function destroy(Chat $chat): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        try {
            $chat->delete(); // This will cascade delete messages

            return response()->json([
                'success' => true,
                'message' => 'Chat deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive/unarchive chat
     */
    public function toggleArchive(Chat $chat): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        try {
            $chat->update(['is_archived' => !$chat->is_archived]);

            return response()->json([
                'success' => true,
                'message' => $chat->is_archived ? 'Chat archived' : 'Chat unarchived',
                'data' => ['is_archived' => $chat->is_archived],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle archive status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chat messages with pagination
     */
    public function getMessages(Request $request, Chat $chat): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        try {
            $messages = $chat->messages()
                ->when($request->before_id, function ($query, $beforeId) {
                    $query->where('id', '<', $beforeId);
                })
                ->orderByDesc('created_at')
                ->limit($request->get('limit', 50))
                ->get()
                ->reverse()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $messages,
                'pagination' => [
                    'has_more' => $messages->count() === $request->get('limit', 50),
                    'oldest_id' => $messages->first()?->id,
                    'newest_id' => $messages->last()?->id,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate AI response
     */
    public function regenerateResponse(Chat $chat, Message $message): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        // Check if message is the last AI message
        if ($message->sender !== 'assistant' || $message->chat_id !== $chat->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid message for regeneration',
            ], 422);
        }

        try {
            $user = Auth::user();

            // Get the user message that prompted this response
            $userMessage = $chat->messages()
                ->where('sender', 'user')
                ->where('created_at', '<', $message->created_at)
                ->orderByDesc('created_at')
                ->first();

            if (!$userMessage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot find original user message',
                ], 422);
            }

            DB::beginTransaction();

            // Delete the old AI response
            $message->delete();

            // Generate new AI response
            $aiResponse = $this->generateAIResponse($chat, $userMessage->content);
            
            if (!$aiResponse) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to regenerate AI response',
                ], 500);
            }

            // Check if user has enough credits
            if (!$user->hasCredits($aiResponse['credits_consumed'])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient credits for regeneration',
                    'data' => [
                        'required_credits' => $aiResponse['credits_consumed'],
                        'current_balance' => $user->credits_balance,
                    ],
                ], 402);
            }

            // Deduct credits and update chat
            $user->deductCredits($aiResponse['credits_consumed']);
            $chat->increment('credits_used', $aiResponse['credits_consumed']);

            // Record analytics
            Analytics::record('messages_regenerated');
            Analytics::record('credits_consumed', $aiResponse['credits_consumed'], (string)$user->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Response regenerated successfully',
                'data' => [
                    'ai_message' => $aiResponse['message'],
                    'credits_used' => $aiResponse['credits_consumed'],
                    'user_credits_remaining' => $user->fresh()->credits_balance,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate response',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export chat conversation
     */
    public function export(Chat $chat, Request $request): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        try {
            $format = $request->get('format', 'json');
            $chat->load(['aiAssistant:id,name', 'messages']);

            switch ($format) {
                case 'txt':
                    $content = $this->exportChatAsText($chat);
                    $mimeType = 'text/plain';
                    break;
                
                case 'json':
                default:
                    $content = $this->exportChatAsJson($chat);
                    $mimeType = 'application/json';
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'content' => $content,
                    'filename' => "chat_{$chat->id}_{$chat->created_at->format('Y-m-d')}.{$format}",
                    'mime_type' => $mimeType,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chat statistics
     */
    public function statistics(Chat $chat): JsonResponse
    {
        // Check ownership
        if ($chat->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        try {
            $stats = [
                'total_messages' => $chat->messages()->count(),
                'user_messages' => $chat->messages()->where('sender', 'user')->count(),
                'ai_messages' => $chat->messages()->where('sender', 'assistant')->count(),
                'total_credits_used' => $chat->credits_used,
                'average_response_time' => $this->calculateAverageResponseTime($chat),
                'chat_duration' => $chat->created_at->diffInMinutes($chat->updated_at),
                'first_message' => $chat->created_at->toISOString(),
                'last_activity' => $chat->updated_at->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get chat statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate AI response for a message
     */
    private function generateAIResponse(Chat $chat, string $userMessage): ?array
    {
        try {
            $assistant = $chat->aiAssistant;
            
            // Get conversation history
            $conversationHistory = $this->buildConversationHistory($chat, $assistant->conversation_memory ?? 10);
            
            // Generate response using OpenAI service
            $response = $this->openAIService->generateResponse(
                $assistant,
                $userMessage,
                $conversationHistory
            );

            if (!$response) {
                return null;
            }

            // Calculate credits consumed (1 credit per character)
            $creditsConsumed = strlen($response['content']);

            // Create AI message
            $aiMessage = Message::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $response['content'],
                'metadata' => [
                    'type' => 'text',
                    'model' => $assistant->model,
                    'temperature' => $assistant->temperature,
                    'response_time' => $response['response_time'] ?? null,
                ],
                'tokens_used' => $response['tokens_used'] ?? 0,
                'credits_consumed' => $creditsConsumed,
            ]);

            return [
                'message' => $aiMessage,
                'credits_consumed' => $creditsConsumed,
            ];

        } catch (\Exception $e) {
            \Log::error('AI response generation failed', [
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build conversation history for AI context
     */
    private function buildConversationHistory(Chat $chat, int $maxMessages = 10): array
    {
        $messages = $chat->messages()
            ->orderByDesc('created_at')
            ->limit($maxMessages)
            ->get()
            ->reverse()
            ->values();

        return $messages->map(function ($message) {
            return [
                'role' => $message->sender === 'user' ? 'user' : 'assistant',
                'content' => $message->content,
            ];
        })->toArray();
    }

    /**
     * Check if content contains blocked words
     */
    private function containsBlockedWords(string $content, array $blockedWords): bool
    {
        if (empty($blockedWords)) {
            return false;
        }

        $content = strtolower($content);
        foreach ($blockedWords as $word) {
            if (strpos($content, strtolower($word)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate average response time for chat
     */
    private function calculateAverageResponseTime(Chat $chat): ?float
    {
        $messages = $chat->messages()->orderBy('created_at')->get();
        $responseTimes = [];

        for ($i = 1; $i < $messages->count(); $i++) {
            $prevMessage = $messages[$i - 1];
            $currentMessage = $messages[$i];

            // Only count AI responses to user messages
            if ($prevMessage->sender === 'user' && $currentMessage->sender === 'assistant') {
                $responseTime = $prevMessage->created_at->diffInSeconds($currentMessage->created_at);
                $responseTimes[] = $responseTime;
            }
        }

        return !empty($responseTimes) ? round(array_sum($responseTimes) / count($responseTimes), 2) : null;
    }

    /**
     * Export chat as text
     */
    private function exportChatAsText(Chat $chat): string
    {
        $content = "Chat with {$chat->aiAssistant->name}\n";
        $content .= "Created: {$chat->created_at->toDateTimeString()}\n";
        $content .= "Credits Used: {$chat->credits_used}\n";
        $content .= str_repeat('=', 50) . "\n\n";

        foreach ($chat->messages as $message) {
            $sender = $message->sender === 'user' ? 'You' : $chat->aiAssistant->name;
            $timestamp = $message->created_at->format('Y-m-d H:i:s');
            
            $content .= "[{$timestamp}] {$sender}:\n";
            $content .= $message->content . "\n\n";
        }

        return $content;
    }

    /**
     * Export chat as JSON
     */
    private function exportChatAsJson(Chat $chat): string
    {
        $data = [
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
                'created_at' => $chat->created_at->toISOString(),
                'credits_used' => $chat->credits_used,
                'ai_assistant' => [
                    'name' => $chat->aiAssistant->name,
                    'model' => $chat->aiAssistant->model,
                ],
            ],
            'messages' => $chat->messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'content' => $message->content,
                    'type' => $message->type,
                    'created_at' => $message->created_at->toISOString(),
                    'credits_consumed' => $message->credits_consumed,
                ];
            }),
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}