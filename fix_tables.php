<?php
// Phoenix AI - Simple Database Table Fix
// This script creates the missing database tables

echo "<h1>🗄️ Phoenix AI - Database Table Fix</h1>";

// Get database configuration (same method as debug.php)
$envFile = __DIR__ . '/backend/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    preg_match('/DB_HOST=(.*)/', $env, $host);
    preg_match('/DB_DATABASE=(.*)/', $env, $db);
    preg_match('/DB_USERNAME=(.*)/', $env, $user);
    preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
    
    if (isset($host[1], $db[1], $user[1], $pass[1])) {
        try {
            // Connect to database (same method as debug.php)
            $pdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
            echo "<p>✅ Database connection: Connected</p>";
            
            // Check current tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Current tables: " . count($tables) . "</p>";
            
            if (count($tables) === 0) {
                echo "<h2>🔧 Creating Tables...</h2>";
                
                // Create users table
                $pdo->exec("CREATE TABLE users (
                    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
                    name varchar(255) NOT NULL,
                    email varchar(255) NOT NULL UNIQUE,
                    password varchar(255) NOT NULL,
                    role enum('admin','user','moderator') DEFAULT 'user',
                    credits_balance int DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p>✅ Users table created</p>";
                
                // Create categories table
                $pdo->exec("CREATE TABLE categories (
                    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
                    name varchar(255) NOT NULL,
                    description text,
                    icon varchar(255) DEFAULT '🤖',
                    is_active tinyint(1) DEFAULT 1,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p>✅ Categories table created</p>";
                
                // Create ai_assistants table
                $pdo->exec("CREATE TABLE ai_assistants (
                    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
                    name varchar(255) NOT NULL,
                    description text NOT NULL,
                    system_prompt text NOT NULL,
                    category_id bigint unsigned NOT NULL,
                    model varchar(50) DEFAULT 'gpt-3.5-turbo',
                    is_active tinyint(1) DEFAULT 1,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p>✅ AI Assistants table created</p>";
                
                // Create chats table
                $pdo->exec("CREATE TABLE chats (
                    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
                    user_id bigint unsigned NOT NULL,
                    ai_assistant_id bigint unsigned NOT NULL,
                    title varchar(255),
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p>✅ Chats table created</p>";
                
                // Create messages table
                $pdo->exec("CREATE TABLE messages (
                    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
                    chat_id bigint unsigned NOT NULL,
                    role enum('user','assistant') NOT NULL,
                    content text NOT NULL,
                    credits_used int DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p>✅ Messages table created</p>";
                
                // Create credit_packages table
                $pdo->exec("CREATE TABLE credit_packages (
                    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
                    name varchar(255) NOT NULL,
                    credits int NOT NULL,
                    price_cents int NOT NULL,
                    description text,
                    is_popular tinyint(1) DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p>✅ Credit packages table created</p>";
                
                // Create transactions table
                $pdo->exec("CREATE TABLE transactions (
                    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
                    user_id bigint unsigned NOT NULL,
                    credit_package_id bigint unsigned,
                    credits_purchased int NOT NULL,
                    price_cents int NOT NULL,
                    payment_method enum('stripe','paypal','bank_deposit') NOT NULL,
                    status enum('pending','completed','failed','cancelled') DEFAULT 'pending',
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<p>✅ Transactions table created</p>";
                
                // Create admin user
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, credits_balance) VALUES (?, ?, ?, 'admin', 1000)");
                $stmt->execute(['Admin User', 'vpersonmail@gmail.com', $adminPassword]);
                echo "<p style='font-weight:bold;color:green;'>✅ Admin user created: vpersonmail@gmail.com</p>";
                
                // Add basic category and AI assistant
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute(['General Assistant', 'General purpose AI assistant']);
                $categoryId = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO ai_assistants (name, description, system_prompt, category_id) VALUES (?, ?, ?, ?)");
                $stmt->execute(['Phoenix Assistant', 'Your helpful AI assistant', 'You are a helpful AI assistant.', $categoryId]);
                
                // Add basic credit packages
                $packages = [
                    ['Starter Pack', 1000, 999, 'Perfect for trying out Phoenix AI'],
                    ['Popular Pack', 5000, 3999, 'Most popular choice'],
                    ['Pro Pack', 15000, 9999, 'Best value for power users']
                ];
                $stmt = $pdo->prepare("INSERT INTO credit_packages (name, credits, price_cents, description, is_popular) VALUES (?, ?, ?, ?, ?)");
                foreach ($packages as $i => $package) {
                    $stmt->execute([$package[0], $package[1], $package[2], $package[3], $i === 1 ? 1 : 0]);
                }
                echo "<p>✅ Basic data seeded</p>";
                
                // Final verification
                $finalTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo "<h2 style='color:green;'>🎉 SUCCESS!</h2>";
                echo "<p><strong>Tables created:</strong> " . count($finalTables) . "</p>";
                echo "<p><strong>Admin login:</strong> vpersonmail@gmail.com / admin123</p>";
                echo "<p><strong>Next:</strong> <a href='/health_check.php'>Run Health Check</a> to verify</p>";
                
            } else {
                echo "<h2>⚠️ Tables Already Exist</h2>";
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>" . htmlspecialchars($table) . "</li>";
                }
                echo "</ul>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color:red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Could not read database configuration</p>";
    }
} else {
    echo "<p style='color:red;'>❌ .env file not found</p>";
}

echo "<p style='margin-top:20px;'>";
echo "<a href='/debug.php' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔍 Debug Info</a> ";
echo "<a href='/health_check.php' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;margin-left:10px;'>🏥 Health Check</a>";
echo "</p>";
?>