<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Response;
use PDO;

class InstallerController extends Controller
{
    public function requirements(): void
    {
        $lock = dirname(__DIR__, 2) . '/install/installed.lock';
        if (file_exists($lock)) {
            Response::redirect('/');
        }
        $requirements = [
            'php_version' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'ext_pdo' => extension_loaded('pdo'),
            'ext_pdo_mysql' => extension_loaded('pdo_mysql'),
            'ext_mbstring' => extension_loaded('mbstring'),
            'ext_curl' => extension_loaded('curl'),
            'ext_json' => extension_loaded('json'),
            'ext_openssl' => extension_loaded('openssl'),
            'storage_writable' => is_writable(dirname(__DIR__, 2) . '/storage'),
        ];
        $allOk = !in_array(false, $requirements, true);
        $this->view('install/requirements', compact('requirements', 'allOk'));
    }

    public function requirementsPost(): void
    {
        Response::redirect('/install/database');
    }

    public function database(): void
    {
        $this->view('install/database');
    }

    public function databasePost(): void
    {
        $host = $_POST['db_host'] ?? 'localhost';
        $port = (int)($_POST['db_port'] ?? 3306);
        $db = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\Throwable $e) {
            Session::flash('error', 'Database connection failed: ' . $e->getMessage());
            Response::redirect('/install/database');
            return;
        }

        $env = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $this->detectAppUrl(),
            'APP_KEY' => bin2hex(random_bytes(16)),
            'DB_HOST' => $host,
            'DB_PORT' => (string)$port,
            'DB_DATABASE' => $db,
            'DB_USERNAME' => $user,
            'DB_PASSWORD' => $pass,
        ];
        $this->writeEnv($env);

        $schemaPath = dirname(__DIR__, 2) . '/install/schema.sql';
        if (is_file($schemaPath)) {
            $sql = file_get_contents($schemaPath) ?: '';
            $pdo->exec($sql);
        }

        Response::redirect('/install/admin');
    }

    public function admin(): void
    {
        $this->view('install/admin');
    }

    public function adminPost(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            Session::flash('error', 'All fields are required');
            Response::redirect('/install/admin');
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $db = new \PDO(
            'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role, status, credits, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $email, $hash, 'admin', 'active', 0]);

        Response::redirect('/install/finish');
    }

    public function finish(): void
    {
        $lock = dirname(__DIR__, 2) . '/install/installed.lock';
        file_put_contents($lock, 'installed at ' . date('c'));
        $this->view('install/finish');
    }

    private function writeEnv(array $values): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        $lines = [];
        foreach ($values as $k => $v) {
            $lines[] = $k . '=' . $v;
        }
        file_put_contents($envFile, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
    }

    private function detectAppUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}