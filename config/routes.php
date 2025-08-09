<?php
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\InstallerController;
use App\Controllers\AuthController;
use App\Controllers\AssistantController;
use App\Controllers\Admin\AssistantAdminController;
use App\Controllers\Admin\SettingAdminController;
use App\Controllers\ChatController;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);

// Installer
$router->get('/install', [InstallerController::class, 'requirements']);
$router->post('/install/requirements', [InstallerController::class, 'requirementsPost']);
$router->get('/install/database', [InstallerController::class, 'database']);
$router->post('/install/database', [InstallerController::class, 'databasePost']);
$router->get('/install/admin', [InstallerController::class, 'admin']);
$router->post('/install/admin', [InstallerController::class, 'adminPost']);
$router->get('/install/finish', [InstallerController::class, 'finish']);

// Auth
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'loginPost']);
$router->post('/logout', [AuthController::class, 'logout']);

// Assistants (public)
$router->get('/assistants', [AssistantController::class, 'index']);
$router->get('/a/{slug}', [AssistantController::class, 'show']);
$router->post('/a/{slug}/message', [ChatController::class, 'postMessage']);

// Admin Assistants
$router->get('/admin/assistants', [AssistantAdminController::class, 'index']);
$router->get('/admin/assistants/create', [AssistantAdminController::class, 'create']);
$router->post('/admin/assistants', [AssistantAdminController::class, 'store']);
$router->get('/admin/assistants/{id}/edit', [AssistantAdminController::class, 'edit']);
$router->post('/admin/assistants/{id}', [AssistantAdminController::class, 'update']);
$router->post('/admin/assistants/{id}/delete', [AssistantAdminController::class, 'destroy']);

// Admin Settings
$router->get('/admin/settings', [SettingAdminController::class, 'index']);
$router->post('/admin/settings', [SettingAdminController::class, 'save']);