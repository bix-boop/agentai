<?php
namespace App\Core;

class Config
{
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (empty(self::$cache)) {
            self::load();
        }
        $segments = explode('.', $key);
        $value = self::$cache;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    private static function load(): void
    {
        $app = [];
        $appPath = CONFIG_PATH . '/app.php';
        if (is_file($appPath)) {
            $app = require $appPath;
        }
        $env = [
            'app' => [
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
                'url' => $_ENV['APP_URL'] ?? '',
                'key' => $_ENV['APP_KEY'] ?? '',
            ],
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => (int)($_ENV['DB_PORT'] ?? 3306),
                'database' => $_ENV['DB_DATABASE'] ?? '',
                'username' => $_ENV['DB_USERNAME'] ?? '',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ],
            'mail' => [
                'host' => $_ENV['MAIL_HOST'] ?? '',
                'port' => (int)($_ENV['MAIL_PORT'] ?? 0),
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
                'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Phoenix AI',
            ],
            'services' => [
                'openai_key' => $_ENV['OPENAI_API_KEY'] ?? '',
                'stripe_public' => $_ENV['STRIPE_PUBLIC'] ?? '',
                'stripe_secret' => $_ENV['STRIPE_SECRET'] ?? '',
                'paypal_client_id' => $_ENV['PAYPAL_CLIENT_ID'] ?? '',
                'paypal_secret' => $_ENV['PAYPAL_SECRET'] ?? '',
            ],
            'security' => [
                'recaptcha_site' => $_ENV['SECURITY_RECAPTCHA_SITE'] ?? '',
                'recaptcha_secret' => $_ENV['SECURITY_RECAPTCHA_SECRET'] ?? '',
            ],
        ];
        self::$cache = array_replace_recursive($app, $env);
    }
}