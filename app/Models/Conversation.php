<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Conversation
{
    public static function create(?int $userId, int $assistantId, array $opts = []): int
    {
        $stmt = DB::pdo()->prepare('INSERT INTO conversations (user_id, assistant_id, lang, tone, style, memory_limit, created_at) VALUES (?,?,?,?,?, ?, NOW())');
        $stmt->execute([
            $userId,
            $assistantId,
            $opts['lang'] ?? null,
            $opts['tone'] ?? null,
            $opts['style'] ?? null,
            (int)($opts['memory_limit'] ?? 8),
        ]);
        return (int)DB::pdo()->lastInsertId();
    }
}