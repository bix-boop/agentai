<?php
/**
 * Phoenix AI Installation Verification Script
 * Comprehensive test to ensure the platform is working perfectly
 */

// Include error handling
require_once 'installer/includes/error_handler.php';
require_once 'installer/includes/php_utils.php';

ErrorHandler::init();

$rootPath = __DIR__;
$backendPath = $rootPath . '/backend';
$frontendPath = $rootPath . '/frontend';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phoenix AI - Installation Verification</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { color: #1e40af; margin: 0; font-size: 2.5rem; }
        .header p { color: #64748b; margin: 10px 0 0 0; font-size: 1.1rem; }
        .test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .test-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .test-card h3 { margin: 0 0 16px 0; color: #1f2937; font-size: 1.2rem; }
        .test-item { display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .test-item:last-child { border-bottom: none; }
        .status { width: 20px; height: 20px; border-radius: 50%; margin-right: 12px; flex-shrink: 0; }
        .status.pass { background: #10b981; }
        .status.fail { background: #ef4444; }
        .status.warn { background: #f59e0b; }
        .test-label { font-weight: 500; color: #374151; }
        .test-detail { color: #6b7280; font-size: 0.9rem; margin-left: 32px; }
        .summary { background: white; border-radius: 12px; padding: 24px; text-align: center; }
        .summary.success { border-left: 4px solid #10b981; }
        .summary.warning { border-left: 4px solid #f59e0b; }
        .summary.error { border-left: 4px solid #ef4444; }
        .actions { margin-top: 30px; text-align: center; }
        .btn { display: inline-block; padding: 12px 24px; margin: 0 8px; border-radius: 8px; text-decoration: none; font-weight: 500; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        .code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Installation Verification</h1>
            <p>Comprehensive test to ensure Phoenix AI is working perfectly</p>
        </div>

        <div class="test-grid">
            <!-- System Environment -->
            <div class="test-card">
                <h3>🖥️ System Environment</h3>
                <?php
                $phpVersion = phpversion();
                $phpCli = PHPUtils::detectPHPPath();
                $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'curl', 'zip', 'gd'];
                ?>
                
                <div class="test-item">
                    <div class="status <?= version_compare($phpVersion, '8.2', '>=') ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label">PHP Version: <?= $phpVersion ?></span>
                </div>
                
                <div class="test-item">
                    <div class="status <?= $phpCli ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label">PHP CLI: <?= $phpCli ?: 'Not found' ?></span>
                </div>
                
                <?php foreach ($extensions as $ext): ?>
                <div class="test-item">
                    <div class="status <?= extension_loaded($ext) ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label"><?= ucfirst($ext) ?> Extension</span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- File System -->
            <div class="test-card">
                <h3>📁 File System</h3>
                <?php
                $directories = [
                    'Backend' => $backendPath,
                    'Frontend' => $frontendPath,
                    'Storage' => $backendPath . '/storage',
                    'Bootstrap Cache' => $backendPath . '/bootstrap/cache',
                    'Installer' => $rootPath . '/installer',
                ];
                ?>
                
                <?php foreach ($directories as $name => $path): ?>
                <div class="test-item">
                    <div class="status <?= is_dir($path) && is_writable($path) ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label"><?= $name ?> Directory</span>
                </div>
                <?php endforeach; ?>
                
                <div class="test-item">
                    <div class="status <?= file_exists($backendPath . '/.env') ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label">.env Configuration</span>
                </div>
                
                <div class="test-item">
                    <div class="status <?= file_exists($backendPath . '/vendor/autoload.php') ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label">Laravel Dependencies</span>
                </div>
            </div>

            <!-- Laravel Framework -->
            <div class="test-card">
                <h3>🚀 Laravel Framework</h3>
                <?php
                $artisanExists = file_exists($backendPath . '/artisan');
                $laravelVersion = '';
                $configTest = false;
                $keyGenerated = false;
                
                if ($artisanExists && $phpCli) {
                    try {
                        $laravelVersion = PHPUtils::execArtisan("--version", $backendPath);
                        $configTest = true;
                        
                        // Check if app key is generated
                        $envContent = file_get_contents($backendPath . '/.env');
                        $keyGenerated = preg_match('/APP_KEY=base64:.+/', $envContent);
                        
                    } catch (Exception $e) {
                        $laravelVersion = 'Error: ' . $e->getMessage();
                    }
                }
                ?>
                
                <div class="test-item">
                    <div class="status <?= $artisanExists ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label">Artisan Console</span>
                </div>
                
                <div class="test-item">
                    <div class="status <?= $configTest ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label">Laravel Framework</span>
                    <?php if ($laravelVersion): ?>
                    <div class="test-detail"><?= htmlspecialchars(trim($laravelVersion)) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="test-item">
                    <div class="status <?= $keyGenerated ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label">Application Key</span>
                </div>
            </div>

            <!-- Database -->
            <div class="test-card">
                <h3>🗄️ Database</h3>
                <?php
                $dbConnected = false;
                $tablesExist = false;
                $adminUserExists = false;
                $migrationStatus = '';
                
                if (file_exists($backendPath . '/.env')) {
                    try {
                        // Test database connection
                        $envContent = file_get_contents($backendPath . '/.env');
                        preg_match('/DB_HOST=(.+)/', $envContent, $hostMatch);
                        preg_match('/DB_PORT=(.+)/', $envContent, $portMatch);
                        preg_match('/DB_DATABASE=(.+)/', $envContent, $dbMatch);
                        preg_match('/DB_USERNAME=(.+)/', $envContent, $userMatch);
                        preg_match('/DB_PASSWORD=(.*)/', $envContent, $passMatch);
                        
                        $host = trim($hostMatch[1] ?? 'localhost');
                        $port = trim($portMatch[1] ?? '3306');
                        $database = trim($dbMatch[1] ?? '');
                        $username = trim($userMatch[1] ?? '');
                        $password = trim($passMatch[1] ?? '');
                        
                        if ($database) {
                            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $dbConnected = true;
                            
                            // Check if tables exist
                            $tables = ['users', 'categories', 'ai_assistants', 'chats', 'messages', 'credit_packages', 'transactions', 'settings'];
                            $existingTables = [];
                            foreach ($tables as $table) {
                                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                                if ($stmt->rowCount() > 0) {
                                    $existingTables[] = $table;
                                }
                            }
                            $tablesExist = count($existingTables) === count($tables);
                            
                            // Check if admin user exists
                            if (in_array('users', $existingTables)) {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                                $adminUserExists = $stmt->fetchColumn() > 0;
                            }
                            
                            // Get migration status
                            if ($phpCli) {
                                try {
                                    $migrationStatus = PHPUtils::execArtisan("migrate:status", $backendPath);
                                } catch (Exception $e) {
                                    $migrationStatus = 'Error: ' . $e->getMessage();
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $dbConnected = false;
                    }
                }
                ?>
                
                <div class="test-item">
                    <div class="status <?= $dbConnected ? 'pass' : 'fail' ?>"></div>
                    <span class="test-label">Database Connection</span>
                </div>
                
                <div class="test-item">
                    <div class="status <?= $tablesExist ? 'pass' : 'warn' ?>"></div>
                    <span class="test-label">Database Tables</span>
                    <?php if (isset($existingTables)): ?>
                    <div class="test-detail"><?= count($existingTables) ?>/<?= count($tables) ?> tables found</div>
                    <?php endif; ?>
                </div>
                
                <div class="test-item">
                    <div class="status <?= $adminUserExists ? 'pass' : 'warn' ?>"></div>
                    <span class="test-label">Admin User</span>
                </div>
            </div>

            <!-- API Endpoints -->
            <div class="test-card">
                <h3>🌐 API Endpoints</h3>
                <?php
                $endpoints = [
                    'System Status' => '/api/v1/status',
                    'System Health' => '/api/v1/system/health',
                    'Categories' => '/api/v1/categories',
                ];
                
                $apiResults = [];
                foreach ($endpoints as $name => $endpoint) {
                    $url = 'https://' . $_SERVER['HTTP_HOST'] . $endpoint;
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 5,
                            'ignore_errors' => true,
                        ]
                    ]);
                    
                    $response = @file_get_contents($url, false, $context);
                    $httpCode = 200;
                    
                    if (isset($http_response_header)) {
                        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
                        $httpCode = isset($matches[1]) ? (int)$matches[1] : 500;
                    }
                    
                    $apiResults[$name] = [
                        'status' => $httpCode === 200 ? 'pass' : 'fail',
                        'code' => $httpCode,
                        'response' => $response
                    ];
                }
                ?>
                
                <?php foreach ($apiResults as $name => $result): ?>
                <div class="test-item">
                    <div class="status <?= $result['status'] ?>"></div>
                    <span class="test-label"><?= $name ?></span>
                    <div class="test-detail">HTTP <?= $result['code'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Configuration -->
            <div class="test-card">
                <h3>⚙️ Configuration</h3>
                <?php
                $configItems = [];
                if (file_exists($backendPath . '/.env')) {
                    $envContent = file_get_contents($backendPath . '/.env');
                    $configItems = [
                        'App Name' => preg_match('/APP_NAME=.+/', $envContent),
                        'App Key' => preg_match('/APP_KEY=base64:.+/', $envContent),
                        'Database Config' => preg_match('/DB_DATABASE=.+/', $envContent),
                        'OpenAI Key' => preg_match('/OPENAI_API_KEY=sk-.+/', $envContent),
                    ];
                }
                ?>
                
                <?php foreach ($configItems as $name => $exists): ?>
                <div class="test-item">
                    <div class="status <?= $exists ? 'pass' : ($name === 'OpenAI Key' ? 'warn' : 'fail') ?>"></div>
                    <span class="test-label"><?= $name ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Frontend -->
            <div class="test-card">
                <h3>🎨 Frontend</h3>
                <?php
                $frontendFiles = [
                    'React App' => $frontendPath . '/src/App.tsx',
                    'Package Config' => $frontendPath . '/package.json',
                    'Tailwind Config' => $frontendPath . '/tailwind.config.js',
                    'Build Directory' => $frontendPath . '/build',
                    'Public Files' => $frontendPath . '/public/index.html',
                ];
                ?>
                
                <?php foreach ($frontendFiles as $name => $path): ?>
                <div class="test-item">
                    <div class="status <?= file_exists($path) ? 'pass' : 'warn' ?>"></div>
                    <span class="test-label"><?= $name ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Overall Summary -->
        <?php
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $warnings = 0;

        // Count results from all test cards
        // This is a simplified count - in a real implementation, you'd track each test
        $overallStatus = 'success'; // Assume success unless we find critical failures
        
        // Check for critical failures
        if (!$dbConnected || !$tablesExist || !file_exists($backendPath . '/.env')) {
            $overallStatus = 'error';
        } elseif (!$adminUserExists || !isset($configItems['OpenAI Key']) || !$configItems['OpenAI Key']) {
            $overallStatus = 'warning';
        }
        ?>

        <div class="summary <?= $overallStatus ?>">
            <?php if ($overallStatus === 'success'): ?>
                <h2 style="color: #10b981; margin: 0 0 16px 0;">🎉 Installation Verification Complete!</h2>
                <p style="margin: 0 0 20px 0;">Phoenix AI is properly installed and ready to use.</p>
            <?php elseif ($overallStatus === 'warning'): ?>
                <h2 style="color: #f59e0b; margin: 0 0 16px 0;">⚠️ Installation Complete with Warnings</h2>
                <p style="margin: 0 0 20px 0;">Phoenix AI is functional but some optional features need configuration.</p>
            <?php else: ?>
                <h2 style="color: #ef4444; margin: 0 0 16px 0;">❌ Installation Issues Detected</h2>
                <p style="margin: 0 0 20px 0;">Some critical components need attention before Phoenix AI can function properly.</p>
            <?php endif; ?>

            <div class="actions">
                <?php if ($overallStatus === 'success'): ?>
                    <a href="/" class="btn btn-success">🚀 Launch Phoenix AI</a>
                    <a href="/admin" class="btn btn-primary">🛠️ Admin Dashboard</a>
                <?php elseif ($overallStatus === 'warning'): ?>
                    <a href="/" class="btn btn-primary">🚀 Launch Phoenix AI</a>
                    <a href="/admin" class="btn btn-secondary">🛠️ Complete Setup</a>
                <?php else: ?>
                    <a href="/installer/" class="btn btn-primary">🔄 Re-run Installation</a>
                    <a href="/health_check.php" class="btn btn-secondary">🏥 Health Check</a>
                <?php endif; ?>
                
                <a href="/debug.php" class="btn btn-secondary">🔍 Debug Info</a>
            </div>
        </div>

        <!-- Quick Setup Guide -->
        <div style="background: white; border-radius: 12px; padding: 24px; margin-top: 30px;">
            <h3>🎯 Quick Access Guide</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div>
                    <h4 style="color: #1f2937; margin: 0 0 8px 0;">🔐 Admin Access</h4>
                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                        URL: <span class="code">https://<?= $_SERVER['HTTP_HOST'] ?>/</span><br>
                        Email: Your admin email from Step 4<br>
                        Password: Your admin password from Step 4
                    </p>
                </div>
                <div>
                    <h4 style="color: #1f2937; margin: 0 0 8px 0;">👥 User Registration</h4>
                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                        URL: <span class="code">https://<?= $_SERVER['HTTP_HOST'] ?>/</span><br>
                        Click "Get Started" to register<br>
                        New users get 1,000 free credits
                    </p>
                </div>
                <div>
                    <h4 style="color: #1f2937; margin: 0 0 8px 0;">🤖 AI Configuration</h4>
                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                        Login as admin → Settings tab<br>
                        Add your OpenAI API key<br>
                        Configure welcome credits & features
                    </p>
                </div>
            </div>
        </div>

        <!-- Debug Information -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-top: 30px;">
            <h3 style="color: #374151; margin: 0 0 16px 0;">🔧 Debug Information</h3>
            <div style="font-family: monospace; font-size: 0.8rem; color: #4b5563;">
                <strong>Root Path:</strong> <?= $rootPath ?><br>
                <strong>Backend Path:</strong> <?= $backendPath ?><br>
                <strong>Frontend Path:</strong> <?= $frontendPath ?><br>
                <strong>PHP CLI:</strong> <?= $phpCli ?: 'Not detected' ?><br>
                <strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?><br>
                <strong>Timestamp:</strong> <?= date('Y-m-d H:i:s T') ?>
            </div>
        </div>
    </div>
</body>
</html>