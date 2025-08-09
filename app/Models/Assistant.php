<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Assistant
{
    public static function all(): array
    {
        $stmt = DB::pdo()->query('SELECT * FROM assistants ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function allActive(): array
    {
        $stmt = DB::pdo()->query('SELECT * FROM assistants WHERE visibility = "public" ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM assistants WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM assistants WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = DB::pdo()->prepare('INSERT INTO assistants (name, slug, expertise, description, avatar_path, training, config_json, visibility, created_at) VALUES (?,?,?,?,?,?,?, ?, NOW())');
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['expertise'] ?? '',
            $data['description'] ?? '',
            $data['avatar_path'] ?? null,
            $data['training'] ?? '',
            $data['config_json'] ?? json_encode([]),
            $data['visibility'] ?? 'public',
        ]);
        return (int)DB::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $fields = ['name','slug','expertise','description','training','config_json','visibility'];
        $set = [];
        $params = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $set[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (array_key_exists('avatar_path', $data)) {
            $set[] = 'avatar_path = ?';
            $params[] = $data['avatar_path'];
        }
        if (!$set) return;
        $params[] = $id;
        $sql = 'UPDATE assistants SET ' . implode(',', $set) . ', updated_at = NOW() WHERE id = ?';
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute($params);
    }

    public static function delete(int $id): void
    {
        $stmt = DB::pdo()->prepare('DELETE FROM assistants WHERE id = ?');
        $stmt->execute([$id]);
    }
}