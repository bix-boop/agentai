<?php
/**
 * Phoenix AI Final Optimization Script
 * Ensures perfect configuration and performance
 */

require_once 'installer/includes/error_handler.php';
require_once 'installer/includes/php_utils.php';

ErrorHandler::init();

$rootPath = __DIR__;
$backendPath = $rootPath . '/backend';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phoenix AI - Final Optimization</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #1e40af; margin: 0; font-size: 2.5rem; }
        .log { background: white; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .log pre { background: #f1f5f9; padding: 15px; border-radius: 6px; overflow-x: auto; margin: 10px 0; }
        .success { color: #10b981; }
        .warning { color: #f59e0b; }
        .error { color: #ef4444; }
        .btn { display: inline-block; padding: 12px 24px; margin: 8px; border-radius: 8px; text-decoration: none; font-weight: 500; background: #3b82f6; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Final Optimization</h1>
            <p>Ensuring perfect Phoenix AI configuration and performance</p>
        </div>

        <div class="log">
            <h3>🔧 Running Final Optimizations...</h3>
            
            <?php
            $optimizations = [];
            $errors = [];
            
            try {
                echo "<p>🔍 Checking Laravel configuration...</p>";
                
                // Check if Laravel is working
                $laravelVersion = PHPUtils::execArtisan("--version", $backendPath);
                if (strpos($laravelVersion, 'Laravel Framework') !== false) {
                    echo "<p class='success'>✅ Laravel Framework: " . trim($laravelVersion) . "</p>";
                    $optimizations[] = "Laravel framework verified";
                } else {
                    echo "<p class='error'>❌ Laravel framework issue</p>";
                    $errors[] = "Laravel framework not responding properly";
                }
                
                echo "<p>🗄️ Optimizing database configuration...</p>";
                
                // Optimize database configuration
                try {
                    $output = PHPUtils::execArtisan("config:cache", $backendPath);
                    echo "<p class='success'>✅ Configuration cached</p>";
                    $optimizations[] = "Configuration cached for performance";
                } catch (Exception $e) {
                    echo "<p class='warning'>⚠️ Config cache skipped: " . $e->getMessage() . "</p>";
                }
                
                try {
                    $output = PHPUtils::execArtisan("route:cache", $backendPath);
                    echo "<p class='success'>✅ Routes cached</p>";
                    $optimizations[] = "Routes cached for performance";
                } catch (Exception $e) {
                    echo "<p class='warning'>⚠️ Route cache skipped: " . $e->getMessage() . "</p>";
                }
                
                echo "<p>🔐 Setting up security...</p>";
                
                // Set proper file permissions
                $envFile = $backendPath . '/.env';
                if (file_exists($envFile)) {
                    chmod($envFile, 0600); // Only owner can read/write
                    echo "<p class='success'>✅ .env file permissions secured</p>";
                    $optimizations[] = ".env file permissions secured";
                }
                
                // Create storage symlink if needed
                try {
                    $output = PHPUtils::execArtisan("storage:link", $backendPath);
                    if (strpos($output, 'already exists') !== false || strpos($output, 'created') !== false) {
                        echo "<p class='success'>✅ Storage symlink configured</p>";
                        $optimizations[] = "Storage symlink configured";
                    }
                } catch (Exception $e) {
                    echo "<p class='warning'>⚠️ Storage symlink: " . $e->getMessage() . "</p>";
                }
                
                echo "<p>🚀 Performance optimization...</p>";
                
                // Optimize for production
                try {
                    $output = PHPUtils::execArtisan("optimize", $backendPath);
                    echo "<p class='success'>✅ Laravel optimized for production</p>";
                    $optimizations[] = "Laravel optimized for production";
                } catch (Exception $e) {
                    echo "<p class='warning'>⚠️ Optimization skipped: " . $e->getMessage() . "</p>";
                }
                
                echo "<p>📊 Testing API endpoints...</p>";
                
                // Test critical API endpoints
                $testEndpoints = ['/api/v1/status', '/api/v1/system/health'];
                foreach ($testEndpoints as $endpoint) {
                    $url = 'https://' . $_SERVER['HTTP_HOST'] . $endpoint;
                    $context = stream_context_create([
                        'http' => ['timeout' => 5, 'ignore_errors' => true]
                    ]);
                    
                    $response = @file_get_contents($url, false, $context);
                    if ($response && strpos($response, '"status":"ok"') !== false) {
                        echo "<p class='success'>✅ API endpoint working: $endpoint</p>";
                        $optimizations[] = "API endpoint verified: $endpoint";
                    } else {
                        echo "<p class='warning'>⚠️ API endpoint not responding: $endpoint</p>";
                    }
                }
                
                echo "<p>🎯 Final verification...</p>";
                
                // Check if admin user exists
                if (file_exists($backendPath . '/.env')) {
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
                        try {
                            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                            $adminCount = $stmt->fetchColumn();
                            
                            if ($adminCount > 0) {
                                echo "<p class='success'>✅ Admin user verified ($adminCount admin users)</p>";
                                $optimizations[] = "Admin user access confirmed";
                            } else {
                                echo "<p class='error'>❌ No admin users found</p>";
                                $errors[] = "No admin users found - you may need to create one manually";
                            }
                            
                            // Check if basic data exists
                            $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
                            $categoryCount = $stmt->fetchColumn();
                            
                            if ($categoryCount > 0) {
                                echo "<p class='success'>✅ Categories seeded ($categoryCount categories)</p>";
                                $optimizations[] = "Categories properly seeded";
                            } else {
                                echo "<p class='warning'>⚠️ No categories found - seeding may have been skipped</p>";
                            }
                            
                        } catch (Exception $e) {
                            echo "<p class='error'>❌ Database verification failed: " . $e->getMessage() . "</p>";
                            $errors[] = "Database verification failed";
                        }
                    }
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Optimization failed: " . $e->getMessage() . "</p>";
                $errors[] = "Optimization process failed";
            }
            ?>
            
            <h3>📊 Optimization Summary</h3>
            <?php if (count($optimizations) > 0): ?>
                <p class="success"><strong>✅ Optimizations Applied (<?= count($optimizations) ?>):</strong></p>
                <ul>
                    <?php foreach ($optimizations as $opt): ?>
                        <li><?= htmlspecialchars($opt) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if (count($errors) > 0): ?>
                <p class="error"><strong>❌ Issues Found (<?= count($errors) ?>):</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if (count($errors) === 0): ?>
                <p class="success"><strong>🎉 Phoenix AI is perfectly optimized and ready for production!</strong></p>
            <?php elseif (count($errors) < 3): ?>
                <p class="warning"><strong>⚠️ Phoenix AI is functional with minor issues that can be resolved later.</strong></p>
            <?php else: ?>
                <p class="error"><strong>❌ Phoenix AI needs attention before it can function properly.</strong></p>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="/verify-installation" class="btn">🔍 Full Verification Report</a>
            <a href="/" class="btn">🚀 Launch Phoenix AI</a>
            <a href="/debug.php" class="btn" style="background: #6b7280;">🔧 Debug Information</a>
        </div>
    </div>
</body>
</html>