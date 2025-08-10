<?php
// Include PHP utilities
require_once __DIR__ . '/installer/includes/php_utils.php';

echo "<h1>🗄️ Phoenix AI - Database Setup</h1>";
echo "<h2>📋 Creating Missing Database Tables</h2>";

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
            echo "<p><strong>Current tables:</strong> " . count($tables) . " found</p>";
            
            if (count($tables) === 0) {
                echo "<h3>🔧 Creating Phoenix AI Tables...</h3>";
                
                // Create users table
                $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    email_verified_at timestamp NULL DEFAULT NULL,
                    password varchar(255) NOT NULL,
                    remember_token varchar(100) DEFAULT NULL,
                    role enum('admin','user','moderator') NOT NULL DEFAULT 'user',
                    credits_balance int NOT NULL DEFAULT '0',
                    date_of_birth date DEFAULT NULL,
                    location varchar(255) DEFAULT NULL,
                    language varchar(10) NOT NULL DEFAULT 'en',
                    timezone varchar(50) NOT NULL DEFAULT 'UTC',
                    tier enum('free','premium','enterprise') NOT NULL DEFAULT 'free',
                    tier_expires_at timestamp NULL DEFAULT NULL,
                    last_login_at timestamp NULL DEFAULT NULL,
                    failed_login_attempts int NOT NULL DEFAULT '0',
                    locked_until timestamp NULL DEFAULT NULL,
                    first_chat_at timestamp NULL DEFAULT NULL,
                    password_changed_at timestamp NULL DEFAULT NULL,
                    preferences json DEFAULT NULL,
                    created_at timestamp NULL DEFAULT NULL,
                    updated_at timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY users_email_unique (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                echo "<p>✅ Created users table</p>";
                
                // Create categories table
                $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    description text,
                    icon varchar(255) DEFAULT NULL,
                    color varchar(7) DEFAULT '#667eea',
                    sort_order int NOT NULL DEFAULT '0',
                    is_active tinyint(1) NOT NULL DEFAULT '1',
                    created_at timestamp NULL DEFAULT NULL,
                    updated_at timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                echo "<p>✅ Created categories table</p>";
                
                // Create credit_packages table  
                $pdo->exec("CREATE TABLE IF NOT EXISTS credit_packages (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    credits int NOT NULL,
                    price_cents int NOT NULL,
                    description text,
                    is_popular tinyint(1) NOT NULL DEFAULT '0',
                    is_active tinyint(1) NOT NULL DEFAULT '1',
                    sort_order int NOT NULL DEFAULT '0',
                    created_at timestamp NULL DEFAULT NULL,
                    updated_at timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                echo "<p>✅ Created credit_packages table</p>";
                
                // Create essential tables
                $essentialTables = [
                    "ai_assistants" => "CREATE TABLE IF NOT EXISTS ai_assistants (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        name varchar(255) NOT NULL,
                        description text NOT NULL,
                        system_prompt text NOT NULL,
                        category_id bigint unsigned NOT NULL,
                        model varchar(50) NOT NULL DEFAULT 'gpt-3.5-turbo',
                        temperature decimal(3,2) NOT NULL DEFAULT '0.70',
                        max_tokens int NOT NULL DEFAULT '2000',
                        is_active tinyint(1) NOT NULL DEFAULT '1',
                        created_at timestamp NULL DEFAULT NULL,
                        updated_at timestamp NULL DEFAULT NULL,
                        PRIMARY KEY (id),
                        KEY ai_assistants_category_id_foreign (category_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    "chats" => "CREATE TABLE IF NOT EXISTS chats (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        user_id bigint unsigned NOT NULL,
                        ai_assistant_id bigint unsigned NOT NULL,
                        title varchar(255) DEFAULT NULL,
                        is_active tinyint(1) NOT NULL DEFAULT '1',
                        created_at timestamp NULL DEFAULT NULL,
                        updated_at timestamp NULL DEFAULT NULL,
                        PRIMARY KEY (id),
                        KEY chats_user_id_foreign (user_id),
                        KEY chats_ai_assistant_id_foreign (ai_assistant_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    "messages" => "CREATE TABLE IF NOT EXISTS messages (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        chat_id bigint unsigned NOT NULL,
                        role enum('user','assistant') NOT NULL,
                        content text NOT NULL,
                        credits_used int NOT NULL DEFAULT '0',
                        created_at timestamp NULL DEFAULT NULL,
                        updated_at timestamp NULL DEFAULT NULL,
                        PRIMARY KEY (id),
                        KEY messages_chat_id_foreign (chat_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    "transactions" => "CREATE TABLE IF NOT EXISTS transactions (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        user_id bigint unsigned NOT NULL,
                        credit_package_id bigint unsigned DEFAULT NULL,
                        credits_purchased int NOT NULL,
                        price_cents int NOT NULL,
                        payment_method enum('stripe','paypal','bank_deposit') NOT NULL,
                        status enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
                        created_at timestamp NULL DEFAULT NULL,
                        updated_at timestamp NULL DEFAULT NULL,
                        PRIMARY KEY (id),
                        KEY transactions_user_id_foreign (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                ];
                
                foreach ($essentialTables as $tableName => $sql) {
                    $pdo->exec($sql);
                    echo "<p>✅ Created $tableName table</p>";
                }
                
                // Create admin user
                echo "<h3>👤 Creating Admin User</h3>";
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, credits_balance, email_verified_at, created_at, updated_at) 
                                     VALUES (?, ?, ?, 'admin', 1000, NOW(), NOW(), NOW()) 
                                     ON DUPLICATE KEY UPDATE password = VALUES(password), updated_at = NOW()");
                $stmt->execute(['Admin User', 'vpersonmail@gmail.com', $adminPassword]);
                echo "<p>✅ Admin user created: vpersonmail@gmail.com</p>";
                
                // Verify final setup
                $finalTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo "<h3>🎉 Setup Complete!</h3>";
                echo "<p><strong>Tables created:</strong> " . count($finalTables) . "</p>";
                echo "<p><strong>Admin login:</strong> vpersonmail@gmail.com / admin123</p>";
                
            } else {
                echo "<p><strong>Tables already exist:</strong></p>";
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>" . htmlspecialchars($table) . "</li>";
                }
                echo "</ul>";
            }
            
        } catch (PDOException $e) {
            echo "<p><strong>Database error:</strong> ❌ " . htmlspecialchars($e->getMessage()) . "</p>";
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

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li><a href='/backend/public'>Try Backend Direct Access</a></li>";
echo "<li><a href='/installer/'>Run Installer Again</a></li>";
echo "<li><a href='/frontend/public/index.html'>Try Frontend</a></li>";
echo "</ul>";
?>