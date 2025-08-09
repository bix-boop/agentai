<?php
namespace App\Core;

class CSRF
{
    public static function token(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf'] = $token;
        return $token;
    }

    public static function verify(?string $token): bool
    {
        $valid = isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string)$token);
        unset($_SESSION['_csrf']);
        return $valid;
    }
}