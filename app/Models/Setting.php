<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Setting
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = DB::pdo()->prepare('SELECT `value` FROM settings WHERE `key` = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        if ($value === false) return $default;
        return (string)$value;
    }

    public static function set(string $key, ?string $value): void
    {
        $stmt = DB::pdo()->prepare('REPLACE INTO settings (`key`, `value`) VALUES (?, ?)');
        $stmt->execute([$key, $value]);
    }

    public static function all(): array
    {
        $stmt = DB::pdo()->query('SELECT `key`,`value` FROM settings');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }
}