<?php
/**
 * Phoenix AI - Comprehensive Health Check
 * Tests all system components and provides detailed diagnostics
 */

// Include utilities
require_once __DIR__ . '/installer/includes/php_utils.php';
require_once __DIR__ . '/installer/includes/error_handler.php';

// Initialize error handling
ErrorHandler::init();

echo "<!DOCTYPE html>";
echo "<html><head><title>Phoenix AI - Health Check</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
    .test-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
    .test-pass { color: #28a745; }
    .test-fail { color: #dc3545; }
    .test-warn { color: #ffc107; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background: #f1f1f1; }
    .status-pass { background: #d4edda; }
    .status-fail { background: #f8d7da; }
    .status-warn { background: #fff3cd; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .action-buttons { margin: 20px 0; }
    .btn { padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-warning { background: #ffc107; color: black; }
    .btn-danger { background: #dc3545; color: white; }
</style></head><body>";

echo "<h1>🏥 Phoenix AI - Comprehensive Health Check</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s T') . "</p>";

$overallStatus = 'PASS';
$criticalIssues = [];
$warnings = [];

// Test 1: System Environment
echo "<div class='test-section'>";
echo "<h2>🖥️ System Environment</h2>";
echo "<table>";
echo "<tr><th>Component</th><th>Status</th><th>Details</th></tr>";

// PHP Version
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.1', '>=');
echo "<tr class='" . ($phpOk ? 'status-pass' : 'status-fail') . "'>";
echo "<td>PHP Version</td>";
echo "<td>" . ($phpOk ? '✅ PASS' : '❌ FAIL') . "</td>";
echo "<td>$phpVersion" . ($phpOk ? '' : ' (requires 8.1+)') . "</td>";
echo "</tr>";
if (!$phpOk) {
    $criticalIssues[] = "PHP version too old: $phpVersion (requires 8.1+)";
    $overallStatus = 'FAIL';
}

// PHP CLI
try {
    $phpPath = PHPUtils::detectPHPPath();
    $cliVersion = PHPUtils::getPHPVersion();
    echo "<tr class='status-pass'>";
    echo "<td>PHP CLI</td>";
    echo "<td>✅ PASS</td>";
    echo "<td>$phpPath (v$cliVersion)</td>";
    echo "</tr>";
} catch (Exception $e) {
    echo "<tr class='status-fail'>";
    echo "<td>PHP CLI</td>";
    echo "<td>❌ FAIL</td>";
    echo "<td>" . htmlspecialchars($e->getMessage()) . "</td>";
    echo "</tr>";
    $criticalIssues[] = "PHP CLI not available: " . $e->getMessage();
    $overallStatus = 'FAIL';
}

// Required Extensions
$required = ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'gd', 'json', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'tokenizer', 'xml', 'zip'];
$missingExt = [];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        $missingExt[] = $ext;
    }
}

if (empty($missingExt)) {
    echo "<tr class='status-pass'>";
    echo "<td>PHP Extensions</td>";
    echo "<td>✅ PASS</td>";
    echo "<td>All required extensions loaded</td>";
    echo "</tr>";
} else {
    echo "<tr class='status-fail'>";
    echo "<td>PHP Extensions</td>";
    echo "<td>❌ FAIL</td>";
    echo "<td>Missing: " . implode(', ', $missingExt) . "</td>";
    echo "</tr>";
    $criticalIssues[] = "Missing PHP extensions: " . implode(', ', $missingExt);
    $overallStatus = 'FAIL';
}

echo "</table>";
echo "</div>";

// Test 2: File System
echo "<div class='test-section'>";
echo "<h2>📁 File System</h2>";
echo "<table>";
echo "<tr><th>Component</th><th>Status</th><th>Details</th></tr>";

$directories = [
    'Backend' => __DIR__ . '/backend',
    'Frontend' => __DIR__ . '/frontend',
    'Installer' => __DIR__ . '/installer',
];

foreach ($directories as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    
    echo "<tr class='" . ($exists && $writable ? 'status-pass' : 'status-fail') . "'>";
    echo "<td>$name Directory</td>";
    echo "<td>" . ($exists && $writable ? '✅ PASS' : '❌ FAIL') . "</td>";
    echo "<td>$path " . ($exists ? ($writable ? '(writable)' : '(read-only)') : '(missing)') . "</td>";
    echo "</tr>";
    
    if (!$exists) {
        $criticalIssues[] = "$name directory missing: $path";
        $overallStatus = 'FAIL';
    } elseif (!$writable) {
        $warnings[] = "$name directory not writable: $path";
    }
}

// Laravel artisan
$artisanPath = __DIR__ . '/backend/artisan';
$artisanExists = file_exists($artisanPath);
echo "<tr class='" . ($artisanExists ? 'status-pass' : 'status-fail') . "'>";
echo "<td>Laravel Artisan</td>";
echo "<td>" . ($artisanExists ? '✅ PASS' : '❌ FAIL') . "</td>";
echo "<td>$artisanPath</td>";
echo "</tr>";

if (!$artisanExists) {
    $criticalIssues[] = "Laravel artisan not found";
    $overallStatus = 'FAIL';
}

echo "</table>";
echo "</div>";

// Test 3: Configuration
echo "<div class='test-section'>";
echo "<h2>⚙️ Configuration</h2>";
echo "<table>";
echo "<tr><th>Component</th><th>Status</th><th>Details</th></tr>";

// .env file
$envPath = __DIR__ . '/backend/.env';
$envExists = file_exists($envPath);
echo "<tr class='" . ($envExists ? 'status-pass' : 'status-warn') . "'>";
echo "<td>.env File</td>";
echo "<td>" . ($envExists ? '✅ PASS' : '⚠️ MISSING') . "</td>";
echo "<td>$envPath</td>";
echo "</tr>";

if (!$envExists) {
    $warnings[] = ".env file not found - installation required";
}

// Database configuration (if .env exists)
if ($envExists) {
    try {
        $env = file_get_contents($envPath);
        preg_match('/DB_HOST=(.*)/', $env, $host);
        preg_match('/DB_DATABASE=(.*)/', $env, $db);
        preg_match('/DB_USERNAME=(.*)/', $env, $user);
        preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
        
        if (isset($host[1], $db[1], $user[1])) {
            $pdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1] ?? '');
            
            // Check tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $expectedTables = ['users', 'categories', 'ai_assistants', 'chats', 'messages', 'transactions', 'credit_packages'];
            $missingTables = array_diff($expectedTables, $tables);
            
            if (empty($missingTables)) {
                echo "<tr class='status-pass'>";
                echo "<td>Database</td>";
                echo "<td>✅ PASS</td>";
                echo "<td>Connected with " . count($tables) . " tables</td>";
                echo "</tr>";
            } else {
                echo "<tr class='status-warn'>";
                echo "<td>Database</td>";
                echo "<td>⚠️ INCOMPLETE</td>";
                echo "<td>Missing tables: " . implode(', ', $missingTables) . "</td>";
                echo "</tr>";
                $warnings[] = "Database missing tables: " . implode(', ', $missingTables);
            }
        } else {
            echo "<tr class='status-fail'>";
            echo "<td>Database</td>";
            echo "<td>❌ FAIL</td>";
            echo "<td>Configuration incomplete</td>";
            echo "</tr>";
            $criticalIssues[] = "Database configuration incomplete";
            $overallStatus = 'FAIL';
        }
    } catch (Exception $e) {
        echo "<tr class='status-fail'>";
        echo "<td>Database</td>";
        echo "<td>❌ FAIL</td>";
        echo "<td>" . htmlspecialchars($e->getMessage()) . "</td>";
        echo "</tr>";
        $criticalIssues[] = "Database connection failed: " . $e->getMessage();
        $overallStatus = 'FAIL';
    }
}

echo "</table>";
echo "</div>";

// Test 4: Laravel Functionality
echo "<div class='test-section'>";
echo "<h2>🔧 Laravel Functionality</h2>";

if ($envExists && $artisanExists) {
    try {
        $backendPath = __DIR__ . '/backend';
        
        echo "<h3>Laravel Commands Test</h3>";
        echo "<table>";
        echo "<tr><th>Command</th><th>Status</th><th>Output</th></tr>";
        
        $commands = [
            '--version' => 'Version Check',
            'route:list --columns=method,uri,name' => 'Route List',
            'config:show app.name' => 'Config Test'
        ];
        
        foreach ($commands as $cmd => $desc) {
            try {
                $output = PHPUtils::execArtisan($cmd, $backendPath);
                $success = !empty($output) && strpos($output, 'command not found') === false;
                
                echo "<tr class='" . ($success ? 'status-pass' : 'status-fail') . "'>";
                echo "<td>$desc</td>";
                echo "<td>" . ($success ? '✅ PASS' : '❌ FAIL') . "</td>";
                echo "<td><pre>" . htmlspecialchars(substr($output, 0, 200)) . (strlen($output) > 200 ? '...' : '') . "</pre></td>";
                echo "</tr>";
                
                if (!$success) {
                    $warnings[] = "Laravel command failed: $cmd";
                }
                
            } catch (Exception $e) {
                echo "<tr class='status-fail'>";
                echo "<td>$desc</td>";
                echo "<td>❌ FAIL</td>";
                echo "<td>" . htmlspecialchars($e->getMessage()) . "</td>";
                echo "</tr>";
                $warnings[] = "Laravel command error: $cmd - " . $e->getMessage();
            }
        }
        
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p class='test-fail'>❌ Laravel test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        $criticalIssues[] = "Laravel functionality test failed: " . $e->getMessage();
        $overallStatus = 'FAIL';
    }
} else {
    echo "<p class='test-warn'>⚠️ Skipping Laravel tests (missing .env or artisan)</p>";
}

echo "</div>";

// Test 5: API Endpoints
echo "<div class='test-section'>";
echo "<h2>🌐 API Endpoints</h2>";

$apiTests = [
    '/api/v1/status' => 'System Status',
    '/api/v1/system/health' => 'Health Check',
    '/api/v1/categories' => 'Categories API',
];

echo "<table>";
echo "<tr><th>Endpoint</th><th>Status</th><th>Response</th></tr>";

foreach ($apiTests as $endpoint => $desc) {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $success = ($httpCode >= 200 && $httpCode < 300);
    
    echo "<tr class='" . ($success ? 'status-pass' : 'status-fail') . "'>";
    echo "<td>$desc</td>";
    echo "<td>" . ($success ? "✅ $httpCode" : "❌ $httpCode") . "</td>";
    echo "<td>";
    
    if ($error) {
        echo "Error: " . htmlspecialchars($error);
    } elseif ($response) {
        $decoded = json_decode($response, true);
        if ($decoded) {
            echo "<pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT)) . "</pre>";
        } else {
            echo "<pre>" . htmlspecialchars(substr($response, 0, 200)) . (strlen($response) > 200 ? '...' : '') . "</pre>";
        }
    } else {
        echo "No response";
    }
    
    echo "</td></tr>";
    
    if (!$success) {
        $warnings[] = "API endpoint not responding: $endpoint (HTTP $httpCode)";
    }
}

echo "</table>";
echo "</div>";

// Test 6: File Permissions
echo "<div class='test-section'>";
echo "<h2>🔐 File Permissions</h2>";

$permissionTests = [
    __DIR__ . '/backend/storage' => 'Laravel Storage',
    __DIR__ . '/backend/bootstrap/cache' => 'Bootstrap Cache',
    __DIR__ . '/backend/.env' => 'Environment File',
    __DIR__ . '/installer' => 'Installer Directory',
];

echo "<table>";
echo "<tr><th>Path</th><th>Exists</th><th>Readable</th><th>Writable</th></tr>";

foreach ($permissionTests as $path => $desc) {
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    $writable = $exists ? is_writable($path) : false;
    
    $status = $exists && $readable && $writable ? 'status-pass' : 'status-warn';
    
    echo "<tr class='$status'>";
    echo "<td>$desc</td>";
    echo "<td>" . ($exists ? '✅' : '❌') . "</td>";
    echo "<td>" . ($readable ? '✅' : '❌') . "</td>";
    echo "<td>" . ($writable ? '✅' : '❌') . "</td>";
    echo "</tr>";
    
    if ($exists && (!$readable || !$writable)) {
        $warnings[] = "Permission issue: $path";
    }
}

echo "</table>";
echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>📊 Summary</h2>";

if ($overallStatus === 'PASS' && empty($criticalIssues)) {
    if (empty($warnings)) {
        echo "<div style='background:#d4edda;padding:20px;border-radius:8px;'>";
        echo "<h3 style='color:#155724;margin:0;'>🎉 All Systems Operational!</h3>";
        echo "<p>Phoenix AI is ready for use. All tests passed successfully.</p>";
        echo "</div>";
    } else {
        echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;'>";
        echo "<h3 style='color:#856404;margin:0;'>⚠️ System Operational with Warnings</h3>";
        echo "<p>Phoenix AI is functional but has some non-critical issues:</p>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li>" . htmlspecialchars($warning) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
} else {
    echo "<div style='background:#f8d7da;padding:20px;border-radius:8px;'>";
    echo "<h3 style='color:#721c24;margin:0;'>❌ Critical Issues Found</h3>";
    echo "<p>Phoenix AI has critical issues that need to be resolved:</p>";
    echo "<ul>";
    foreach ($criticalIssues as $issue) {
        echo "<li>" . htmlspecialchars($issue) . "</li>";
    }
    echo "</ul>";
    if (!empty($warnings)) {
        echo "<p><strong>Additional warnings:</strong></p>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li>" . htmlspecialchars($warning) . "</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
}

echo "</div>";

// Action Buttons
echo "<div class='action-buttons'>";
echo "<h2>🛠️ Available Actions</h2>";

if ($overallStatus === 'FAIL' || !$envExists) {
    echo "<a href='/installer/' class='btn btn-primary'>🚀 Run Installation Wizard</a>";
    echo "<a href='/installer/install.php' class='btn btn-success'>⚡ Quick Install</a>";
}

echo "<a href='/debug.php' class='btn btn-warning'>🔍 Debug Information</a>";
echo "<a href='/test_system.php' class='btn btn-warning'>🧪 System Tests</a>";

if ($envExists) {
    echo "<a href='/run_migrations.php' class='btn btn-primary'>🔄 Run Migrations</a>";
    echo "<a href='/' class='btn btn-success'>🏠 Go to Site</a>";
}

echo "</div>";

// Debug Log
$logContents = ErrorHandler::getLogContents();
if (!empty($logContents) && $logContents !== "No log file found.") {
    echo "<div class='test-section'>";
    echo "<h2>📋 Recent Debug Log</h2>";
    echo "<pre>" . htmlspecialchars($logContents) . "</pre>";
    echo "<a href='?clear_log=1' class='btn btn-danger'>🗑️ Clear Log</a>";
    echo "</div>";
}

// Clear log if requested
if (isset($_GET['clear_log'])) {
    ErrorHandler::clearLog();
    echo "<script>window.location.href = window.location.pathname;</script>";
}

echo "</body></html>";
?>