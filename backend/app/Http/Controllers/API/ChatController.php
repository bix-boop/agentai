<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AIAssistant;
use App\Models\Chat;
use App\Models\Message;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function __construct(
        private OpenAIService $openAIService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user's chats
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $chats = Chat::where('user_id', $user->id)
            ->with(['aiAssistant:id,name,slug,avatar'])
            ->when($request->archived, function ($query) {
                return $query->archived();
            }, function ($query) {
                return $query->active();
            })
            ->recentActivity()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'chats' => $chats,
        ]);
    }

    /**
     * Start a new chat with an AI assistant
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ai_assistant_id' => 'required|exists:ai_assistants,id',
            'message' => 'required|string|min:1|max:5000',
            'settings' => 'sometimes|array',
            'settings.language' => 'sometimes|string|max:50',
            'settings.tone' => 'sometimes|string|max:50',
            'settings.writing_style' => 'sometimes|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $assistant = AIAssistant::findOrFail($request->ai_assistant_id);

        // Check if user can access this AI assistant
        if (!$assistant->canAccess($user)) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have access to this AI assistant.',
            ], 403);
        }

        // Check message length constraints
        $messageLength = strlen($request->message);
        if ($messageLength < $assistant->min_message_length || 
            $messageLength > $assistant->max_message_length) {
            return response()->json([
                'success' => false,
                'error' => "Message must be between {$assistant->min_message_length} and {$assistant->max_message_length} characters.",
            ], 422);
        }

        // Create new chat
        $chat = Chat::create([
            'user_id' => $user->id,
            'ai_assistant_id' => $assistant->id,
            'settings' => $request->settings ?? [],
            'last_activity_at' => now(),
        ]);

        // Send message to OpenAI
        $result = $this->openAIService->sendChatMessage(
            $chat,
            $request->message,
            $request->settings ?? []
        );

        if (!$result['success']) {
            $chat->delete(); // Remove empty chat
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'error_code' => $result['error_code'],
            ], 400);
        }

        // Load chat with messages
        $chat->load(['messages', 'aiAssistant:id,name,slug,avatar']);

        return response()->json([
            'success' => true,
            'chat' => $chat,
            'credits_used' => $result['credits_used'],
            'processing_time' => $result['processing_time'],
        ], 201);
    }

    /**
     * Get a specific chat with messages
     */
    public function show(Chat $chat): JsonResponse
    {
        $user = Auth::user();

        if ($chat->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to chat.',
            ], 403);
        }

        $chat->load([
            'messages' => function ($query) {
                $query->orderBy('created_at');
            },
            'aiAssistant:id,name,slug,avatar,welcome_message'
        ]);

        return response()->json([
            'success' => true,
            'chat' => $chat,
        ]);
    }

    /**
     * Send a message in an existing chat
     */
    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:1|max:5000',
            'settings' => 'sometimes|array',
            'settings.language' => 'sometimes|string|max:50',
            'settings.tone' => 'sometimes|string|max:50',
            'settings.writing_style' => 'sometimes|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if ($chat->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to chat.',
            ], 403);
        }

        if ($chat->is_archived) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot send messages to archived chats.',
            ], 422);
        }

        $assistant = $chat->aiAssistant;

        // Check message length constraints
        $messageLength = strlen($request->message);
        if ($messageLength < $assistant->min_message_length || 
            $messageLength > $assistant->max_message_length) {
            return response()->json([
                'success' => false,
                'error' => "Message must be between {$assistant->min_message_length} and {$assistant->max_message_length} characters.",
            ], 422);
        }

        // Merge settings with chat settings
        $settings = array_merge($chat->settings ?? [], $request->settings ?? []);

        // Send message to OpenAI
        $result = $this->openAIService->sendChatMessage(
            $chat,
            $request->message,
            $settings
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'error_code' => $result['error_code'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'user_message' => $result['user_message'],
            'ai_message' => $result['ai_message'],
            'credits_used' => $result['credits_used'],
            'processing_time' => $result['processing_time'],
            'user_credits_remaining' => $user->fresh()->credits_balance,
        ]);
    }

    /**
     * Archive a chat
     */
    public function archive(Chat $chat): JsonResponse
    {
        $user = Auth::user();

        if ($chat->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to chat.',
            ], 403);
        }

        $chat->update(['is_archived' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Chat archived successfully.',
        ]);
    }

    /**
     * Restore an archived chat
     */
    public function restore(Chat $chat): JsonResponse
    {
        $user = Auth::user();

        if ($chat->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to chat.',
            ], 403);
        }

        $chat->update(['is_archived' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Chat restored successfully.',
        ]);
    }

    /**
     * Delete a chat
     */
    public function destroy(Chat $chat): JsonResponse
    {
        $user = Auth::user();

        if ($chat->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to chat.',
            ], 403);
        }

        $chat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat deleted successfully.',
        ]);
    }

    /**
     * Update chat settings
     */
    public function updateSettings(Request $request, Chat $chat): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.language' => 'sometimes|string|max:50',
            'settings.tone' => 'sometimes|string|max:50',
            'settings.writing_style' => 'sometimes|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if ($chat->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to chat.',
            ], 403);
        }

        $chat->update([
            'settings' => array_merge($chat->settings ?? [], $request->settings)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat settings updated successfully.',
            'settings' => $chat->settings,
        ]);
    }

    /**
     * Generate image in chat
     */
    public function generateImage(Request $request, Chat $chat): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|min:1|max:1000',
            'size' => 'sometimes|in:256x256,512x512,1024x1024',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if ($chat->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to chat.',
            ], 403);
        }

        if (!$chat->aiAssistant->enable_image_generation) {
            return response()->json([
                'success' => false,
                'error' => 'This AI assistant does not support image generation.',
            ], 422);
        }

        $result = $this->openAIService->generateImage(
            $user,
            $request->prompt,
            $request->size ?? '1024x1024'
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'error_code' => $result['error_code'],
            ], 400);
        }

        // Save image generation as a message
        $message = Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Generated image: ' . $request->prompt,
            'credits_consumed' => $result['credits_used'],
            'metadata' => [
                'type' => 'image_generation',
                'prompt' => $request->prompt,
                'images' => $result['images'],
            ],
        ]);

        $chat->addCreditsUsed($result['credits_used']);
        $chat->updateActivity();

        return response()->json([
            'success' => true,
            'message' => $message,
            'images' => $result['images'],
            'credits_used' => $result['credits_used'],
            'user_credits_remaining' => $user->fresh()->credits_balance,
        ]);
    }
}