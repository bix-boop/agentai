<?php
// Include PHP utilities
require_once __DIR__ . '/installer/includes/php_utils.php';

echo "<h1>🔍 Phoenix AI - Enhanced Debug Information</h1>";
echo "<h2>📋 System Status</h2>";

// PHP CLI Detection
echo "<h3>🐘 PHP Configuration</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><td><strong>PHP Version (Web)</strong></td><td>" . PHP_VERSION . "</td></tr>";

try {
    $phpPath = PHPUtils::detectPHPPath();
    $cliVersion = PHPUtils::getPHPVersion();
    echo "<tr><td><strong>PHP CLI Path</strong></td><td style='color:green;'>✅ " . htmlspecialchars($phpPath) . "</td></tr>";
    echo "<tr><td><strong>PHP Version (CLI)</strong></td><td>" . htmlspecialchars($cliVersion) . "</td></tr>";
} catch (Exception $e) {
    echo "<tr><td><strong>PHP CLI Status</strong></td><td style='color:red;'>❌ " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

$composerPath = PHPUtils::checkComposer();
if ($composerPath) {
    echo "<tr><td><strong>Composer</strong></td><td style='color:green;'>✅ Found at " . htmlspecialchars($composerPath) . "</td></tr>";
} else {
    echo "<tr><td><strong>Composer</strong></td><td style='color:orange;'>⚠️ Not found (not required for installation)</td></tr>";
}

echo "</table>";

// Check if .env file exists
$envFile = __DIR__ . '/backend/.env';
echo "<p><strong>.env file path:</strong> " . htmlspecialchars($envFile) . "</p>";
echo "<p><strong>.env file:</strong> " . (file_exists($envFile) ? "✅ Found" : "❌ Missing") . "</p>";

// Also check current directory
echo "<p><strong>Current directory:</strong> " . htmlspecialchars(__DIR__) . "</p>";
echo "<p><strong>Backend directory exists:</strong> " . (is_dir(__DIR__ . '/backend') ? "✅ Yes" : "❌ No") . "</p>";

if (file_exists($envFile)) {
    echo "<p><strong>.env content preview:</strong></p>";
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;'>";
    foreach (array_slice($lines, 0, 15) as $line) {
        if (strpos($line, 'PASSWORD') !== false || strpos($line, 'KEY') !== false) {
            $line = preg_replace('/=.*/', '=***HIDDEN***', $line);
        }
        echo htmlspecialchars($line) . "\n";
    }
    echo "</pre>";
}

// Check Laravel
$laravelPath = __DIR__ . '/backend/artisan';
echo "<p><strong>Laravel artisan:</strong> " . (file_exists($laravelPath) ? "✅ Found" : "❌ Missing") . "</p>";

// Check database connection
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    preg_match('/DB_HOST=(.*)/', $env, $host);
    preg_match('/DB_DATABASE=(.*)/', $env, $db);
    preg_match('/DB_USERNAME=(.*)/', $env, $user);
    preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
    
    if (isset($host[1], $db[1], $user[1], $pass[1])) {
        try {
            $pdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
            echo "<p><strong>Database connection:</strong> ✅ Connected</p>";
            
            // Check if tables exist
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<p><strong>Database tables:</strong> " . count($tables) . " tables found</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";
            
        } catch (PDOException $e) {
            echo "<p><strong>Database connection:</strong> ❌ Failed - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// Check web server
echo "<h2>Web Server Info</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";

// Check if we can access backend
$backendUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/backend/public/api/v1/status';
echo "<p><strong>Backend API test:</strong> ";
$ch = curl_init($backendUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "✅ API responding";
} else {
    echo "❌ API not responding (HTTP $httpCode)";
}
echo "</p>";

// Admin functionality
session_start();

echo "<h2>🎛️ Admin Access</h2>";

// Simple admin authentication
$isAdmin = isset($_SESSION['debug_admin']);
$error = '';

if ($_POST && isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === 'admin123') {
        $_SESSION['debug_admin'] = true;
        $isAdmin = true;
        echo "<p style='color:green;'>✅ Admin access granted!</p>";
    } else {
        $error = 'Invalid admin password';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /debug_admin.php');
    exit;
}

if (!$isAdmin) {
    if ($error) {
        echo "<p style='color:red;'>❌ $error</p>";
    }
    echo "<form method='POST' style='margin:20px 0;'>";
    echo "<p><strong>Admin Password:</strong> <input type='password' name='admin_password' placeholder='Enter admin123' style='padding:8px;margin-left:10px;'> ";
    echo "<input type='submit' value='Access Admin' style='padding:8px 15px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;'></p>";
    echo "</form>";
} else {
    echo "<p style='color:green;'>✅ Admin access active</p>";
    echo "<p><a href='?logout=1' style='color:red;'>Logout</a></p>";
    
    echo "<h3>🗄️ Database Management</h3>";
    
    // Database table creation functionality
    if (isset($_GET['create_tables'])) {
        if (file_exists($envFile)) {
            $env = file_get_contents($envFile);
            preg_match('/DB_HOST=(.*)/', $env, $host);
            preg_match('/DB_DATABASE=(.*)/', $env, $db);
            preg_match('/DB_USERNAME=(.*)/', $env, $user);
            preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
            
            if (isset($host[1], $db[1], $user[1], $pass[1])) {
                try {
                    $adminPdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
                    
                    echo "<p style='color:blue;'>🔧 Creating database tables...</p>";
                    
                    // Create users table
                    $adminPdo->exec("CREATE TABLE IF NOT EXISTS users (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        name varchar(255) NOT NULL,
                        email varchar(255) NOT NULL,
                        email_verified_at timestamp NULL DEFAULT NULL,
                        password varchar(255) NOT NULL,
                        remember_token varchar(100) DEFAULT NULL,
                        role enum('admin','user','moderator') NOT NULL DEFAULT 'user',
                        credits_balance int NOT NULL DEFAULT '0',
                        created_at timestamp NULL DEFAULT NULL,
                        updated_at timestamp NULL DEFAULT NULL,
                        PRIMARY KEY (id),
                        UNIQUE KEY users_email_unique (email)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    
                    // Create admin user
                    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                    $stmt = $adminPdo->prepare("INSERT INTO users (name, email, password, role, credits_balance, email_verified_at, created_at, updated_at) 
                                             VALUES (?, ?, ?, 'admin', 1000, NOW(), NOW(), NOW()) 
                                             ON DUPLICATE KEY UPDATE password = VALUES(password), updated_at = NOW()");
                    $stmt->execute(['Admin User', 'vpersonmail@gmail.com', $adminPassword]);
                    
                    echo "<p style='color:green;'>✅ Database tables and admin user created successfully!</p>";
                    echo "<p><strong>Admin Login:</strong> vpersonmail@gmail.com / admin123</p>";
                    
                } catch (Exception $e) {
                    echo "<p style='color:red;'>❌ Error creating tables: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }
    
    echo "<p><a href='?create_tables=1' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;'>🗄️ Create Database Tables</a></p>";
}

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li><a href='/backend/public'>Try Backend Direct Access</a></li>";
echo "<li><a href='/installer/'>Run Installer Again</a></li>";
echo "<li><a href='/health_check.php'>Health Check</a></li>";
if ($isAdmin) {
    echo "<li><a href='?create_tables=1'>Create Database Tables</a></li>";
}
echo "</ul>";
?>