<?php
namespace App\Controllers;

use App\Core\Response;
use App\Models\Assistant;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use App\Services\OpenAIService;

class ChatController
{
    public function postMessage(string $slug): void
    {
        $assistant = Assistant::findBySlug($slug);
        if (!$assistant) {
            Response::json(['error' => 'Assistant not found'], 404);
        }
        $text = trim($_POST['message'] ?? '');
        if ($text === '') {
            Response::json(['error' => 'Empty message'], 422);
        }
        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        if ($conversationId <= 0) {
            $conversationId = Conversation::create($_SESSION['user_id'] ?? null, (int)$assistant['id']);
        }
        Message::add($conversationId, 'user', $text);

        $openaiKey = Setting::get('services.openai_key') ?: ($_ENV['OPENAI_API_KEY'] ?? '');
        $config = json_decode($assistant['config_json'] ?: '{}', true) ?: [];
        $training = $assistant['training'] ?? '';

        $messages = [];
        if ($training !== '') {
            $messages[] = ['role' => 'system', 'content' => $training];
        }
        foreach (Message::recentByConversation($conversationId, (int)($config['memory_limit'] ?? 8)) as $m) {
            $messages[] = [
                'role' => $m['sender'] === 'user' ? 'user' : 'assistant',
                'content' => $m['content'],
            ];
        }
        $reply = 'This is a placeholder response.';
        if ($openaiKey) {
            $svc = new OpenAIService($openaiKey);
            $reply = $svc->chat($messages, $config);
        }
        Message::add($conversationId, 'assistant', $reply);
        Response::json([
            'conversation_id' => $conversationId,
            'reply' => $reply,
        ]);
    }
}