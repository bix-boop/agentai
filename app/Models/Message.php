<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Message
{
    public static function add(int $conversationId, string $sender, string $content, ?int $tokens = null): int
    {
        $stmt = DB::pdo()->prepare('INSERT INTO messages (conversation_id, sender, content, tokens, created_at) VALUES (?,?,?,?, NOW())');
        $stmt->execute([$conversationId, $sender, $content, $tokens]);
        return (int)DB::pdo()->lastInsertId();
    }

    public static function recentByConversation(int $conversationId, int $limit = 10): array
    {
        $stmt = DB::pdo()->prepare('SELECT sender, content FROM messages WHERE conversation_id = ? ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_reverse($rows);
    }
}