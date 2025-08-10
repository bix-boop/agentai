<?php
// Phoenix AI - Database Fix Script
// This script will try to fix the database connection issues

echo "<h1>🔧 Phoenix AI - Database Fix</h1>";
echo "<pre>";

// Try different database configurations
$configs = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'admin123'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'admin123'],
    ['host' => 'localhost', 'user' => 'agentai', 'pass' => 'agentai'],
    ['host' => 'localhost', 'user' => 'agentai', 'pass' => 'admin123'],
];

$workingConfig = null;

echo "Testing database configurations...\n\n";

foreach ($configs as $i => $config) {
    echo "Testing config " . ($i + 1) . ": {$config['user']}@{$config['host']} with password '" . ($config['pass'] ?: 'EMPTY') . "'...\n";
    
    try {
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "  ✅ MySQL connection successful!\n";
        
        // Check if agentai database exists
        $stmt = $pdo->query("SHOW DATABASES LIKE 'agentai'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ Database 'agentai' exists\n";
            
            // Try to connect to the specific database
            $dsn = "mysql:host={$config['host']};dbname=agentai;charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass']);
            
            echo "  ✅ Connected to agentai database\n";
            
            // Check if users table exists and has data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  ✅ Users table accessible, count: " . $result['count'] . "\n";
            
            // Check for admin user
            $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                echo "  ✅ Admin user found: " . $admin['email'] . " (ID: " . $admin['id'] . ")\n";
            } else {
                echo "  ⚠️  No admin user found\n";
            }
            
            $workingConfig = $config;
            break;
            
        } else {
            echo "  ❌ Database 'agentai' not found\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

if ($workingConfig) {
    echo "🎉 WORKING CONFIGURATION FOUND!\n";
    echo "Host: " . $workingConfig['host'] . "\n";
    echo "User: " . $workingConfig['user'] . "\n";
    echo "Password: " . ($workingConfig['pass'] ?: 'EMPTY') . "\n\n";
    
    echo "Updating .env file...\n";
    
    // Update the .env file
    $envPath = __DIR__ . '/backend/.env';
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        
        // Update database configuration
        $envContent = preg_replace('/DB_HOST=.*/', "DB_HOST={$workingConfig['host']}", $envContent);
        $envContent = preg_replace('/DB_USERNAME=.*/', "DB_USERNAME={$workingConfig['user']}", $envContent);
        $envContent = preg_replace('/DB_PASSWORD=.*/', "DB_PASSWORD={$workingConfig['pass']}", $envContent);
        $envContent = preg_replace('/DB_SOCKET=.*/', "DB_SOCKET=", $envContent);
        
        if (file_put_contents($envPath, $envContent)) {
            echo "✅ .env file updated successfully!\n";
            
            // Clear Laravel caches
            echo "Clearing Laravel caches...\n";
            exec('cd ' . __DIR__ . '/backend && php artisan config:clear 2>&1', $output, $code);
            if ($code === 0) {
                echo "✅ Configuration cache cleared\n";
            } else {
                echo "⚠️  Cache clear warning: " . implode("\n", $output) . "\n";
            }
            
            echo "\n🚀 Database configuration fixed! You can now:\n";
            echo "1. Visit /admin-login.php to access the admin dashboard\n";
            echo "2. Use email: vpersonmail@gmail.com (or admin@legozo.com)\n";
            echo "3. Use password: admin123 (default)\n";
            echo "4. Test API at: /api/v1/status\n";
            
        } else {
            echo "❌ Failed to update .env file\n";
        }
    } else {
        echo "❌ .env file not found\n";
    }
    
} else {
    echo "❌ NO WORKING DATABASE CONFIGURATION FOUND\n";
    echo "This might be a Plesk-specific configuration issue.\n";
    echo "Please check your Plesk database settings or contact your hosting provider.\n";
}

echo "</pre>";
?>

<style>
body { font-family: monospace; margin: 20px; }
h1 { color: #667eea; }
pre { background: #f8f9fa; padding: 20px; border-radius: 5px; }
</style>