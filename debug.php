<?php
// Include PHP utilities
require_once __DIR__ . '/installer/includes/php_utils.php';

// Handle auto-fix functionality
if (isset($_GET['auto_fix'])) {
    echo "<h1>🔧 Phoenix AI - Auto Database Fix</h1>";
    
    $envFile = __DIR__ . '/backend/.env';
    if (file_exists($envFile)) {
        $env = file_get_contents($envFile);
        preg_match('/DB_HOST=(.*)/', $env, $host);
        preg_match('/DB_DATABASE=(.*)/', $env, $db);
        preg_match('/DB_USERNAME=(.*)/', $env, $user);
        preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
        
        if (isset($host[1], $db[1], $user[1], $pass[1])) {
            try {
                $fixPdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
                echo "<p style='color:green;'>✅ Connected for database fix</p>";
                
                // Create essential tables
                $fixPdo->exec("CREATE TABLE users (id bigint unsigned AUTO_INCREMENT PRIMARY KEY, name varchar(255) NOT NULL, email varchar(255) NOT NULL UNIQUE, password varchar(255) NOT NULL, role enum('admin','user') DEFAULT 'user', credits_balance int DEFAULT 0, created_at timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p style='color:green;'>✅ Users table created</p>";
                
                $fixPdo->exec("CREATE TABLE categories (id bigint unsigned AUTO_INCREMENT PRIMARY KEY, name varchar(255) NOT NULL, description text, created_at timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p style='color:green;'>✅ Categories table created</p>";
                
                $fixPdo->exec("CREATE TABLE ai_assistants (id bigint unsigned AUTO_INCREMENT PRIMARY KEY, name varchar(255) NOT NULL, description text NOT NULL, system_prompt text NOT NULL, category_id bigint unsigned NOT NULL, created_at timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p style='color:green;'>✅ AI Assistants table created</p>";
                
                // Create admin user
                $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $fixPdo->prepare("INSERT INTO users (name, email, password, role, credits_balance) VALUES (?, ?, ?, 'admin', 1000)");
                $stmt->execute(['Admin User', 'vpersonmail@gmail.com', $adminPass]);
                echo "<p style='color:green;font-weight:bold;'>✅ Admin user created!</p>";
                
                echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
                echo "<h2 style='color:green;'>🎉 Database Fix Complete!</h2>";
                echo "<p><strong>Admin Login:</strong> vpersonmail@gmail.com</p>";
                echo "<p><strong>Password:</strong> admin123</p>";
                echo "<p><a href='/debug.php'>← Back to Debug Info</a></p>";
                echo "</div>";
                
                exit;
                
            } catch (Exception $e) {
                echo "<p style='color:red;'>❌ Fix failed: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><a href='/debug.php'>← Back to Debug Info</a></p>";
                exit;
            }
        }
    }
}

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
            
            // If no tables found, offer to create them
            if (count($tables) === 0) {
                echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:15px 0;'>";
                echo "<h3 style='color:#856404;'>🚨 Database is Empty!</h3>";
                echo "<p>No tables found. The installation process did not create the database tables.</p>";
                echo "<p><a href='/debug.php?auto_fix=1' style='background:#dc3545;color:white;padding:8px 15px;text-decoration:none;border-radius:4px;'>🔧 Auto-Fix Database</a></p>";
                echo "</div>";
            }
            
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

// Admin functionality added to debug.php
session_start();

echo "<h2>🎛️ Quick Admin Tools</h2>";

$isAdmin = isset($_SESSION['debug_admin_access']);
$adminError = '';

// Handle admin authentication
if ($_POST && isset($_POST['admin_pass'])) {
    if (trim($_POST['admin_pass']) === 'admin123') {
        $_SESSION['debug_admin_access'] = true;
        $isAdmin = true;
        echo "<p style='color:green;font-weight:bold;'>✅ Admin access granted!</p>";
    } else {
        $adminError = 'Invalid password';
    }
}

// Handle logout
if (isset($_GET['admin_logout'])) {
    unset($_SESSION['debug_admin_access']);
    header('Location: /debug.php');
    exit;
}

if (!$isAdmin) {
    if ($adminError) {
        echo "<p style='color:red;'>❌ $adminError</p>";
    }
    echo "<div style='background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;'>";
    echo "<p><strong>🔐 Admin Access Required</strong></p>";
    echo "<form method='POST' style='margin:10px 0;'>";
    echo "Password: <input type='password' name='admin_pass' placeholder='admin123' style='padding:8px;margin:0 10px;'>";
    echo "<input type='submit' value='Login' style='padding:8px 15px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;'>";
    echo "</form>";
    echo "<p><em>Use: admin123</em></p>";
    echo "</div>";
} else {
    echo "<p style='color:green;'>✅ Admin access active | <a href='?admin_logout=1' style='color:red;'>Logout</a></p>";
    
    // Quick database fix functionality
    if (isset($_GET['fix_db'])) {
        echo "<div style='background:#e6f3ff;padding:15px;border-radius:5px;margin:10px 0;'>";
        echo "<h3>🔧 Database Quick Fix</h3>";
        
        if (file_exists($envFile)) {
            $env = file_get_contents($envFile);
            preg_match('/DB_HOST=(.*)/', $env, $host);
            preg_match('/DB_DATABASE=(.*)/', $env, $db);
            preg_match('/DB_USERNAME=(.*)/', $env, $user);
            preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
            
            if (isset($host[1], $db[1], $user[1], $pass[1])) {
                try {
                    $fixPdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
                    
                    // Create essential tables
                    $fixPdo->exec("CREATE TABLE IF NOT EXISTS users (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        name varchar(255) NOT NULL,
                        email varchar(255) NOT NULL,
                        password varchar(255) NOT NULL,
                        role enum('admin','user','moderator') NOT NULL DEFAULT 'user',
                        credits_balance int NOT NULL DEFAULT '0',
                        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        UNIQUE KEY users_email_unique (email)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $fixPdo->exec("CREATE TABLE IF NOT EXISTS categories (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        name varchar(255) NOT NULL,
                        description text,
                        is_active tinyint(1) NOT NULL DEFAULT '1',
                        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    // Create admin user
                    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
                    $stmt = $fixPdo->prepare("INSERT IGNORE INTO users (name, email, password, role, credits_balance) VALUES (?, ?, ?, 'admin', 1000)");
                    $stmt->execute(['Admin User', 'vpersonmail@gmail.com', $adminHash]);
                    
                    echo "<p style='color:green;'>✅ Essential tables and admin user created!</p>";
                    echo "<p><strong>Login:</strong> vpersonmail@gmail.com / admin123</p>";
                    
                } catch (Exception $e) {
                    echo "<p style='color:red;'>❌ Database fix error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
        echo "</div>";
    }
    
    echo "<div style='margin:15px 0;'>";
    echo "<a href='?fix_db=1' style='background:#dc3545;color:white;padding:8px 15px;text-decoration:none;border-radius:4px;margin-right:10px;'>🔧 Quick Database Fix</a>";
    echo "<a href='/health_check.php' style='background:#28a745;color:white;padding:8px 15px;text-decoration:none;border-radius:4px;'>🏥 Re-check Health</a>";
    echo "</div>";
}

// Quick database fix functionality
if (isset($_GET['create_db_tables'])) {
    echo "<h2>🗄️ Creating Database Tables</h2>";
    
    if (file_exists($envFile)) {
        $env = file_get_contents($envFile);
        preg_match('/DB_HOST=(.*)/', $env, $host);
        preg_match('/DB_DATABASE=(.*)/', $env, $db);
        preg_match('/DB_USERNAME=(.*)/', $env, $user);
        preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
        
        if (isset($host[1], $db[1], $user[1], $pass[1])) {
            try {
                $setupPdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
                echo "<p style='color:green;'>✅ Connected to database for setup</p>";
                
                // Create users table
                $setupPdo->exec("CREATE TABLE IF NOT EXISTS users (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    email_verified_at timestamp NULL DEFAULT NULL,
                    password varchar(255) NOT NULL,
                    role enum('admin','user','moderator') NOT NULL DEFAULT 'user',
                    credits_balance int NOT NULL DEFAULT '0',
                    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY users_email_unique (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p style='color:green;'>✅ Created users table</p>";
                
                // Create categories table
                $setupPdo->exec("CREATE TABLE IF NOT EXISTS categories (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    description text,
                    icon varchar(255) DEFAULT '🤖',
                    color varchar(7) DEFAULT '#667eea',
                    is_active tinyint(1) NOT NULL DEFAULT '1',
                    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p style='color:green;'>✅ Created categories table</p>";
                
                // Create ai_assistants table
                $setupPdo->exec("CREATE TABLE IF NOT EXISTS ai_assistants (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    description text NOT NULL,
                    system_prompt text NOT NULL,
                    category_id bigint unsigned NOT NULL,
                    model varchar(50) NOT NULL DEFAULT 'gpt-3.5-turbo',
                    is_active tinyint(1) NOT NULL DEFAULT '1',
                    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p style='color:green;'>✅ Created ai_assistants table</p>";
                
                // Create other essential tables
                $otherTables = [
                    "chats" => "CREATE TABLE IF NOT EXISTS chats (id bigint unsigned AUTO_INCREMENT PRIMARY KEY, user_id bigint unsigned NOT NULL, ai_assistant_id bigint unsigned NOT NULL, title varchar(255), created_at timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    "messages" => "CREATE TABLE IF NOT EXISTS messages (id bigint unsigned AUTO_INCREMENT PRIMARY KEY, chat_id bigint unsigned NOT NULL, role enum('user','assistant') NOT NULL, content text NOT NULL, created_at timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    "credit_packages" => "CREATE TABLE IF NOT EXISTS credit_packages (id bigint unsigned AUTO_INCREMENT PRIMARY KEY, name varchar(255) NOT NULL, credits int NOT NULL, price_cents int NOT NULL, created_at timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    "transactions" => "CREATE TABLE IF NOT EXISTS transactions (id bigint unsigned AUTO_INCREMENT PRIMARY KEY, user_id bigint unsigned NOT NULL, credits_purchased int NOT NULL, price_cents int NOT NULL, payment_method enum('stripe','paypal','bank_deposit') NOT NULL, status enum('pending','completed','failed') DEFAULT 'pending', created_at timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                ];
                
                foreach ($otherTables as $tableName => $sql) {
                    $setupPdo->exec($sql);
                    echo "<p style='color:green;'>✅ Created $tableName table</p>";
                }
                
                // Create admin user
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $setupPdo->prepare("INSERT IGNORE INTO users (name, email, password, role, credits_balance) VALUES (?, ?, ?, 'admin', 1000)");
                $stmt->execute(['Admin User', 'vpersonmail@gmail.com', $adminPassword]);
                echo "<p style='color:green;font-weight:bold;'>✅ Admin user created: vpersonmail@gmail.com</p>";
                
                // Add basic category
                $stmt = $setupPdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute(['General Assistant', 'General purpose AI assistant']);
                
                // Verify setup
                $newTables = $setupPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo "<p style='color:blue;font-weight:bold;'>🎉 Setup complete! Created " . count($newTables) . " tables</p>";
                echo "<p style='background:#d4edda;padding:10px;border-radius:5px;'><strong>✅ You can now login as admin:</strong><br>Email: vpersonmail@gmail.com<br>Password: admin123</p>";
                
            } catch (Exception $e) {
                echo "<p style='color:red;'>❌ Database setup error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
}

echo "<h2>🚀 Quick Actions</h2>";
echo "<div style='margin:20px 0;'>";
echo "<a href='?create_db_tables=1' style='background:#dc3545;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🗄️ Create Database Tables</a>";
echo "<a href='/health_check.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🏥 Health Check</a>";
echo "<a href='/run_migrations.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔄 Run Migrations</a>";
echo "</div>";

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li><a href='/backend/public'>Try Backend Direct Access</a></li>";
echo "<li><a href='/installer/'>Run Installer Again</a></li>";
echo "<li><a href='/health_check.php'>Health Check</a></li>";
echo "<li><strong>🎯 RECOMMENDED:</strong> <a href='?create_db_tables=1'>Create Database Tables</a> to fix the empty database</li>";
echo "</ul>";
?>