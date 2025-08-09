<?php
// Include PHP utilities
require_once __DIR__ . '/installer/includes/php_utils.php';

echo "<h1>🧪 Phoenix AI - Complete System Test</h1>";

$tests = [];
$passed = 0;
$failed = 0;

function runTest($name, $test) {
    global $tests, $passed, $failed;
    
    try {
        $result = $test();
        if ($result === true) {
            $tests[] = ['name' => $name, 'status' => 'PASS', 'message' => 'OK'];
            $passed++;
        } else {
            $tests[] = ['name' => $name, 'status' => 'FAIL', 'message' => $result];
            $failed++;
        }
    } catch (Exception $e) {
        $tests[] = ['name' => $name, 'status' => 'FAIL', 'message' => $e->getMessage()];
        $failed++;
    }
}

// Test 1: Directory Structure
runTest("Directory Structure", function() {
    $required = [
        '/backend',
        '/backend/app',
        '/backend/config',
        '/backend/database',
        '/backend/routes',
        '/backend/artisan',
        '/frontend',
        '/installer',
    ];
    
    foreach ($required as $path) {
        if (!file_exists(__DIR__ . $path)) {
            return "Missing: " . $path;
        }
    }
    return true;
});

// Test 2: PHP Files Syntax
runTest("PHP Syntax Check", function() {
    $files = [
        '/backend/app/Models/User.php',
        '/backend/app/Models/AIAssistant.php',
        '/backend/app/Models/Category.php',
        '/backend/app/Models/Chat.php',
        '/backend/app/Models/Message.php',
        '/backend/app/Models/Transaction.php',
        '/backend/app/Models/CreditPackage.php',
        '/backend/app/Http/Controllers/API/AuthController.php',
        '/backend/app/Http/Controllers/API/ChatController.php',
        '/backend/app/Http/Controllers/API/AIAssistantController.php',
        '/backend/app/Http/Controllers/API/CategoryController.php',
        '/backend/app/Http/Controllers/API/UserController.php',
        '/backend/app/Http/Controllers/API/PaymentController.php',
        '/backend/app/Http/Controllers/API/WebhookController.php',
        '/backend/app/Http/Controllers/Admin/AdminController.php',
        '/backend/app/Http/Controllers/Admin/SettingsController.php',
        '/backend/app/Http/Middleware/AdminMiddleware.php',
    ];
    
    try {
        $phpPath = PHPUtils::detectPHPPath();
        
        foreach ($files as $file) {
            $fullPath = __DIR__ . $file;
            if (!file_exists($fullPath)) {
                return "Missing file: " . $file;
            }
            
            $output = shell_exec("$phpPath -l " . escapeshellarg($fullPath) . " 2>&1");
            if (strpos($output, 'No syntax errors') === false) {
                return "Syntax error in " . $file . ": " . $output;
            }
        }
        return true;
        
    } catch (Exception $e) {
        return "PHP CLI not available: " . $e->getMessage();
    }
});

// Test 3: Laravel Configuration
runTest("Laravel Configuration", function() {
    $backendPath = __DIR__ . '/backend';
    if (!is_dir($backendPath)) {
        return "Backend directory not found";
    }
    
    try {
        // Test artisan with proper PHP path
        $output = PHPUtils::execArtisan("--version", $backendPath);
        if (!strpos($output, 'Laravel')) {
            return "Laravel not working: " . $output;
        }
        
        return true;
        
    } catch (Exception $e) {
        return "Laravel test failed: " . $e->getMessage();
    }
});

// Test 4: Database Migrations
runTest("Database Migrations", function() {
    $migrations = [
        '0001_01_01_000000_create_users_table.php',
        '2025_08_09_193022_create_categories_table.php',
        '2025_08_09_193027_create_ai_assistants_table.php',
        '2025_08_09_193027_create_chats_table.php',
        '2025_08_09_193027_create_messages_table.php',
        '2025_08_09_193027_create_transactions_table.php',
        '2025_08_09_193027_create_credit_packages_table.php',
    ];
    
    foreach ($migrations as $migration) {
        $path = __DIR__ . '/backend/database/migrations/' . $migration;
        if (!file_exists($path)) {
            return "Missing migration: " . $migration;
        }
    }
    
    return true;
});

// Test 5: Installer Files
runTest("Installer Files", function() {
    $files = [
        '/installer/index.php',
        '/installer/install.php',
        '/installer/steps/step1.php',
        '/installer/steps/step2.php',
        '/installer/steps/step3.php',
        '/installer/steps/step4.php',
        '/installer/steps/step5.php',
        '/installer/steps/step6.php',
        '/installer/assets/css/installer.css',
        '/installer/assets/js/installer.js',
    ];
    
    foreach ($files as $file) {
        if (!file_exists(__DIR__ . $file)) {
            return "Missing installer file: " . $file;
        }
    }
    
    return true;
});

// Test 6: Frontend Files
runTest("Frontend Files", function() {
    $files = [
        '/frontend/public/index.html',
        '/frontend/package.json',
        '/frontend/src',
    ];
    
    foreach ($files as $file) {
        if (!file_exists(__DIR__ . $file)) {
            return "Missing frontend file: " . $file;
        }
    }
    
    return true;
});

// Test 7: Web Server Configuration
runTest("Web Server Configuration", function() {
    $files = [
        '/index.php',
        '/.htaccess',
    ];
    
    foreach ($files as $file) {
        if (!file_exists(__DIR__ . $file)) {
            return "Missing web server file: " . $file;
        }
    }
    
    return true;
});

// Test 8: API Route Loading
runTest("API Routes", function() {
    $routeFile = __DIR__ . '/backend/routes/api.php';
    if (!file_exists($routeFile)) {
        return "API routes file missing";
    }
    
    $content = file_get_contents($routeFile);
    $requiredRoutes = [
        'AuthController',
        'ChatController', 
        'AIAssistantController',
        'CategoryController',
        'UserController',
        'PaymentController',
        'AdminController'
    ];
    
    foreach ($requiredRoutes as $controller) {
        if (strpos($content, $controller) === false) {
            return "Missing controller reference: " . $controller;
        }
    }
    
    return true;
});

// Display Results
echo "<h2>📊 Test Results</h2>";
echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
echo "<tr style='background:#f0f0f0;'><th>Test</th><th>Status</th><th>Message</th></tr>";

foreach ($tests as $test) {
    $color = $test['status'] === 'PASS' ? '#d4edda' : '#f8d7da';
    $icon = $test['status'] === 'PASS' ? '✅' : '❌';
    echo "<tr style='background:{$color};'>";
    echo "<td>" . htmlspecialchars($test['name']) . "</td>";
    echo "<td>{$icon} " . htmlspecialchars($test['status']) . "</td>";
    echo "<td>" . htmlspecialchars($test['message']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>📈 Summary</h2>";
echo "<p><strong>Total Tests:</strong> " . count($tests) . "</p>";
echo "<p><strong>Passed:</strong> <span style='color:green;'>{$passed}</span></p>";
echo "<p><strong>Failed:</strong> <span style='color:red;'>{$failed}</span></p>";

if ($failed === 0) {
    echo "<div style='background:#d4edda; padding:20px; border-radius:8px; margin:20px 0;'>";
    echo "<h3>🎉 ALL TESTS PASSED!</h3>";
    echo "<p>Your Phoenix AI system is ready for installation!</p>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da; padding:20px; border-radius:8px; margin:20px 0;'>";
    echo "<h3>⚠️ Some Tests Failed</h3>";
    echo "<p>Please fix the failed tests before proceeding with installation.</p>";
    echo "</div>";
}

echo "<h2>🔗 Next Steps</h2>";
echo "<ul>";
echo "<li><a href='/installer/install.php'>🚀 Quick Install (Recommended)</a></li>";
echo "<li><a href='/installer/'>📋 Step-by-Step Installer</a></li>";
echo "<li><a href='/debug.php'>🔍 Debug Information</a></li>";
echo "<li><a href='/run_migrations.php'>🔄 Test Migrations</a></li>";
echo "</ul>";
?>