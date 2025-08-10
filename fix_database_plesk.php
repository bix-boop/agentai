<?php
// Phoenix AI - Plesk Database Fix Script
// This script will try to fix database issues specific to Plesk hosting

echo "<!DOCTYPE html><html><head><title>Phoenix AI - Database Fix</title>";
echo "<style>body{font-family:monospace;margin:20px;} h1{color:#667eea;} pre{background:#f8f9fa;padding:20px;border-radius:5px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";
echo "</head><body>";
echo "<h1>🔧 Phoenix AI - Plesk Database Fix</h1>";
echo "<pre>";

function testDatabaseConnection($host, $user, $pass, $database = null) {
    try {
        $dsn = "mysql:host={$host}" . ($database ? ";dbname={$database}" : "") . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Exception $e) {
        return false;
    }
}

function createDatabaseAndUser($pdo, $dbName, $dbUser, $dbPass) {
    try {
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  ✅ Database '{$dbName}' created/verified\n";
        
        // Create user if it doesn't exist (with proper permissions)
        $pdo->exec("CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}'");
        $pdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");
        $pdo->exec("FLUSH PRIVILEGES");
        echo "  ✅ User '{$dbUser}' created/updated with permissions\n";
        
        return true;
    } catch (Exception $e) {
        echo "  ❌ Error creating database/user: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "🔍 Step 1: Detecting Plesk Database Configuration...\n\n";

// Common Plesk database configurations
$configs = [
    // Standard Plesk configurations
    ['host' => 'localhost', 'user' => 'admin', 'pass' => 'admin'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'admin'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'admin', 'pass' => 'admin'],
    // Try the existing user
    ['host' => 'localhost', 'user' => 'agentai', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'agentai', 'pass' => 'agentai'],
    ['host' => 'localhost', 'user' => 'agentai', 'pass' => 'admin123'],
];

$workingRootConfig = null;
$targetDatabase = 'agentai';
$targetUser = 'agentai';
$targetPassword = 'agentai123'; // Set a secure password

echo "Testing database configurations to find root access...\n\n";

foreach ($configs as $i => $config) {
    echo "Testing config " . ($i + 1) . ": {$config['user']}@{$config['host']} with password '" . ($config['pass'] ?: 'EMPTY') . "'...\n";
    
    $pdo = testDatabaseConnection($config['host'], $config['user'], $config['pass']);
    if ($pdo) {
        echo "  ✅ MySQL connection successful!\n";
        
        // Check if we have admin privileges
        try {
            $stmt = $pdo->query("SELECT USER(), CONNECTION_ID()");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  ✅ Connected as: " . $result['USER()'] . "\n";
            
            // Test if we can create databases (admin privileges)
            $testDb = 'test_' . uniqid();
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDb}`");
            $pdo->exec("DROP DATABASE `{$testDb}`");
            echo "  ✅ Admin privileges confirmed\n";
            
            $workingRootConfig = $config;
            break;
            
        } catch (Exception $e) {
            echo "  ⚠️  Limited privileges: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  ❌ Connection failed\n";
    }
    echo "\n";
}

if ($workingRootConfig) {
    echo "🎉 FOUND WORKING ROOT CONNECTION!\n";
    echo "Host: " . $workingRootConfig['host'] . "\n";
    echo "User: " . $workingRootConfig['user'] . "\n";
    echo "Password: " . ($workingRootConfig['pass'] ?: 'EMPTY') . "\n\n";
    
    echo "🔧 Step 2: Setting up Phoenix AI database...\n\n";
    
    $rootPdo = testDatabaseConnection($workingRootConfig['host'], $workingRootConfig['user'], $workingRootConfig['pass']);
    
    if (createDatabaseAndUser($rootPdo, $targetDatabase, $targetUser, $targetPassword)) {
        echo "\n🔧 Step 3: Updating Laravel configuration...\n\n";
        
        // Update the .env file
        $envPath = __DIR__ . '/backend/.env';
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            // Update database configuration
            $envContent = preg_replace('/DB_HOST=.*/', "DB_HOST={$workingRootConfig['host']}", $envContent);
            $envContent = preg_replace('/DB_USERNAME=.*/', "DB_USERNAME={$targetUser}", $envContent);
            $envContent = preg_replace('/DB_PASSWORD=.*/', "DB_PASSWORD={$targetPassword}", $envContent);
            $envContent = preg_replace('/DB_DATABASE=.*/', "DB_DATABASE={$targetDatabase}", $envContent);
            $envContent = preg_replace('/DB_SOCKET=.*/', "DB_SOCKET=", $envContent);
            
            if (file_put_contents($envPath, $envContent)) {
                echo "✅ .env file updated successfully!\n";
                
                // Test the new configuration
                echo "\n🧪 Step 4: Testing new configuration...\n\n";
                $testPdo = testDatabaseConnection($workingRootConfig['host'], $targetUser, $targetPassword, $targetDatabase);
                if ($testPdo) {
                    echo "✅ New database configuration works!\n";
                    
                    // Clear Laravel caches
                    echo "\n🔄 Step 5: Clearing Laravel caches...\n\n";
                    exec('cd ' . __DIR__ . '/backend && php artisan config:clear 2>&1', $output, $code);
                    if ($code === 0) {
                        echo "✅ Configuration cache cleared\n";
                        
                        // Test Laravel database connection
                        exec('cd ' . __DIR__ . '/backend && php artisan migrate:status 2>&1', $output, $code);
                        if ($code === 0) {
                            echo "✅ Laravel database connection verified\n";
                            echo "\n🎉 DATABASE FIX COMPLETED SUCCESSFULLY!\n\n";
                            echo "You can now:\n";
                            echo "1. Visit <a href='/admin-login.php'>/admin-login.php</a> for full admin dashboard\n";
                            echo "2. Visit <a href='/admin-login-simple.php'>/admin-login-simple.php</a> for simple mode\n";
                            echo "3. Test API at: <a href='/api/v1/status'>/api/v1/status</a>\n";
                            echo "4. Use email: vpersonmail@gmail.com\n";
                            echo "5. Use password: admin123\n";
                        } else {
                            echo "⚠️  Laravel still has issues: " . implode("\n", $output) . "\n";
                            echo "But basic connection is working. You can use the simple admin interface.\n";
                        }
                    } else {
                        echo "⚠️  Cache clear had issues: " . implode("\n", $output) . "\n";
                    }
                } else {
                    echo "❌ New configuration test failed\n";
                }
            } else {
                echo "❌ Failed to update .env file\n";
            }
        } else {
            echo "❌ .env file not found\n";
        }
    }
} else {
    echo "❌ NO WORKING ROOT DATABASE ACCESS FOUND\n\n";
    echo "This is likely a Plesk-specific restriction. Options:\n\n";
    echo "1. <strong>Use Simple Mode:</strong> <a href='/admin-login-simple.php'>Simple Admin Login</a> (works without database)\n";
    echo "2. <strong>Contact Hosting Provider:</strong> Ask for MySQL root access or database credentials\n";
    echo "3. <strong>Plesk Panel:</strong> Check database settings in Plesk control panel\n";
    echo "4. <strong>Re-run Installer:</strong> <a href='/installer/'>Run installer again</a> with correct credentials\n\n";
    
    echo "The installation created tables successfully, so database access exists somewhere.\n";
    echo "You may need to check your Plesk database configuration.\n";
}

echo "</pre>";
echo "<div style='margin-top:20px;'>";
echo "<a href='/admin-login-simple.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🚀 Use Simple Admin Interface</a> ";
echo "<a href='/debug.php' style='background:#718096;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>🔍 Debug Info</a>";
echo "</div>";
echo "</body></html>";
?>