<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

define('APP_PATH', BASE_PATH . '/app');

define('CONFIG_PATH', BASE_PATH . '/config');

define('STORAGE_PATH', BASE_PATH . '/storage');

define('VIEW_PATH', APP_PATH . '/Views');

// Ensure runtime directories
@mkdir(STORAGE_PATH . '/logs', 0775, true);
@mkdir(STORAGE_PATH . '/cache', 0775, true);
@mkdir(STORAGE_PATH . '/uploads', 0775, true);

// Simple autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Env;
use App\Core\Config;
use App\Core\Router;
use App\Core\Session;
use App\Core\Response;

require_once APP_PATH . '/Core/Env.php';
require_once APP_PATH . '/Core/Config.php';
require_once APP_PATH . '/Core/Router.php';
require_once APP_PATH . '/Core/Session.php';
require_once APP_PATH . '/Core/Response.php';

Env::load(BASE_PATH . '/.env');
Session::start();

$router = new Router();

// Load routes
require CONFIG_PATH . '/routes.php';

// Redirect to installer if not installed
$installLock = BASE_PATH . '/install/installed.lock';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!file_exists($installLock) && strpos($path, '/install') !== 0 && strpos($path, '/assets') !== 0) {
    Response::redirect('/install');
    exit;
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);