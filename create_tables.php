<?php
// Phoenix AI - Create Missing Database Tables
// This script will create the tables that are missing from the database

echo "<h1>🗄️ Phoenix AI - Create Database Tables</h1>";
echo "<pre>";

$envFile = __DIR__ . '/backend/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    preg_match('/DB_HOST=(.*)/', $env, $host);
    preg_match('/DB_DATABASE=(.*)/', $env, $db);
    preg_match('/DB_USERNAME=(.*)/', $env, $user);
    preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
    
    if (isset($host[1], $db[1], $user[1], $pass[1])) {
        try {
            $pdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
            echo "✅ Database connection: Connected\n";
            
            // Check current tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "Current tables: " . count($tables) . "\n\n";
            
            if (count($tables) === 0) {
                echo "🔧 Creating Phoenix AI database tables...\n\n";
                
                // Create migrations table first
                $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
                    id int unsigned NOT NULL AUTO_INCREMENT,
                    migration varchar(255) NOT NULL,
                    batch int NOT NULL,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                echo "✅ Created migrations table\n";
                
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
                echo "✅ Created users table\n";
                
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
                echo "✅ Created categories table\n";
                
                // Create ai_assistants table
                $pdo->exec("CREATE TABLE IF NOT EXISTS ai_assistants (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    description text NOT NULL,
                    system_prompt text NOT NULL,
                    category_id bigint unsigned NOT NULL,
                    model varchar(50) NOT NULL DEFAULT 'gpt-3.5-turbo',
                    temperature decimal(3,2) NOT NULL DEFAULT '0.70',
                    max_tokens int NOT NULL DEFAULT '2000',
                    frequency_penalty decimal(3,2) NOT NULL DEFAULT '0.00',
                    presence_penalty decimal(3,2) NOT NULL DEFAULT '0.00',
                    avatar_url varchar(255) DEFAULT NULL,
                    is_active tinyint(1) NOT NULL DEFAULT '1',
                    is_featured tinyint(1) NOT NULL DEFAULT '0',
                    sort_order int NOT NULL DEFAULT '0',
                    usage_count int NOT NULL DEFAULT '0',
                    language varchar(10) NOT NULL DEFAULT 'en',
                    response_tone enum('professional','casual','friendly','formal','creative') NOT NULL DEFAULT 'professional',
                    writing_style enum('concise','detailed','bullet_points','conversational','academic') NOT NULL DEFAULT 'conversational',
                    created_at timestamp NULL DEFAULT NULL,
                    updated_at timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY ai_assistants_category_id_foreign (category_id),
                    CONSTRAINT ai_assistants_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                echo "✅ Created ai_assistants table\n";
                
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
                echo "✅ Created credit_packages table\n";
                
                // Create chats table
                $pdo->exec("CREATE TABLE IF NOT EXISTS chats (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    user_id bigint unsigned NOT NULL,
                    ai_assistant_id bigint unsigned NOT NULL,
                    title varchar(255) DEFAULT NULL,
                    is_active tinyint(1) NOT NULL DEFAULT '1',
                    created_at timestamp NULL DEFAULT NULL,
                    updated_at timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY chats_user_id_foreign (user_id),
                    KEY chats_ai_assistant_id_foreign (ai_assistant_id),
                    CONSTRAINT chats_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    CONSTRAINT chats_ai_assistant_id_foreign FOREIGN KEY (ai_assistant_id) REFERENCES ai_assistants (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                echo "✅ Created chats table\n";
                
                // Create messages table
                $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    chat_id bigint unsigned NOT NULL,
                    role enum('user','assistant') NOT NULL,
                    content text NOT NULL,
                    credits_used int NOT NULL DEFAULT '0',
                    created_at timestamp NULL DEFAULT NULL,
                    updated_at timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY messages_chat_id_foreign (chat_id),
                    CONSTRAINT messages_chat_id_foreign FOREIGN KEY (chat_id) REFERENCES chats (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                echo "✅ Created messages table\n";
                
                // Create transactions table
                $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    user_id bigint unsigned NOT NULL,
                    credit_package_id bigint unsigned DEFAULT NULL,
                    credits_purchased int NOT NULL,
                    price_cents int NOT NULL,
                    payment_method enum('stripe','paypal','bank_deposit') NOT NULL,
                    payment_intent_id varchar(255) DEFAULT NULL,
                    status enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
                    verification_notes text,
                    completed_at timestamp NULL DEFAULT NULL,
                    created_at timestamp NULL DEFAULT NULL,
                    updated_at timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY transactions_user_id_foreign (user_id),
                    KEY transactions_credit_package_id_foreign (credit_package_id),
                    CONSTRAINT transactions_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    CONSTRAINT transactions_credit_package_id_foreign FOREIGN KEY (credit_package_id) REFERENCES credit_packages (id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                echo "✅ Created transactions table\n";
                
                // Create additional tables
                $additionalTables = [
                    "CREATE TABLE IF NOT EXISTS cache (
                        key varchar(255) NOT NULL,
                        value mediumtext NOT NULL,
                        expiration int NOT NULL,
                        PRIMARY KEY (key)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    "CREATE TABLE IF NOT EXISTS jobs (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        queue varchar(255) NOT NULL,
                        payload longtext NOT NULL,
                        attempts tinyint unsigned NOT NULL,
                        reserved_at int unsigned DEFAULT NULL,
                        available_at int unsigned NOT NULL,
                        created_at int unsigned NOT NULL,
                        PRIMARY KEY (id),
                        KEY jobs_queue_index (queue)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    "CREATE TABLE IF NOT EXISTS personal_access_tokens (
                        id bigint unsigned NOT NULL AUTO_INCREMENT,
                        tokenable_type varchar(255) NOT NULL,
                        tokenable_id bigint unsigned NOT NULL,
                        name varchar(255) NOT NULL,
                        token varchar(64) NOT NULL,
                        abilities text,
                        last_used_at timestamp NULL DEFAULT NULL,
                        expires_at timestamp NULL DEFAULT NULL,
                        created_at timestamp NULL DEFAULT NULL,
                        updated_at timestamp NULL DEFAULT NULL,
                        PRIMARY KEY (id),
                        UNIQUE KEY personal_access_tokens_token_unique (token),
                        KEY personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type,tokenable_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                ];
                
                foreach ($additionalTables as $sql) {
                    $pdo->exec($sql);
                }
                echo "✅ Created additional tables (cache, jobs, personal_access_tokens)\n";
                
                echo "\n🎉 All tables created successfully!\n";
                
                // Now create the admin user
                echo "\n👤 Creating admin user...\n";
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, credits_balance, email_verified_at, created_at, updated_at) 
                                     VALUES (?, ?, ?, 'admin', 1000, NOW(), NOW(), NOW()) 
                                     ON DUPLICATE KEY UPDATE 
                                     name = VALUES(name), 
                                     password = VALUES(password), 
                                     credits_balance = VALUES(credits_balance),
                                     updated_at = NOW()");
                
                $stmt->execute(['Admin User', 'vpersonmail@gmail.com', $adminPassword]);
                echo "✅ Admin user created: vpersonmail@gmail.com\n";
                
                // Create second admin user
                $stmt->execute(['Admin', 'admin@legozo.com', $adminPassword]);
                echo "✅ Admin user created: admin@legozo.com\n";
                
                // Seed some basic data
                echo "\n🌱 Seeding basic data...\n";
                
                // Create categories
                $categories = [
                    ['General Assistant', 'General purpose AI assistant', '🤖', '#667eea'],
                    ['Writing Helper', 'Content creation and writing assistance', '✍️', '#10b981'],
                    ['Code Assistant', 'Programming and development help', '💻', '#f59e0b'],
                    ['Business Advisor', 'Business strategy and analysis', '💼', '#8b5cf6']
                ];
                
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, icon, color, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                foreach ($categories as $i => $category) {
                    $stmt->execute([$category[0], $category[1], $category[2], $category[3], $i]);
                }
                echo "✅ Created " . count($categories) . " categories\n";
                
                // Create credit packages
                $packages = [
                    ['Starter Pack', 1000, 999, 'Perfect for trying out Phoenix AI'],
                    ['Popular Pack', 5000, 3999, 'Most popular choice for regular users'],
                    ['Pro Pack', 15000, 9999, 'Best value for power users'],
                    ['Enterprise Pack', 50000, 29999, 'For businesses and heavy usage']
                ];
                
                $stmt = $pdo->prepare("INSERT INTO credit_packages (name, credits, price_cents, description, is_popular, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                foreach ($packages as $i => $package) {
                    $isPopular = $i === 1 ? 1 : 0; // Second package is popular
                    $stmt->execute([$package[0], $package[1], $package[2], $package[3], $isPopular, $i]);
                }
                echo "✅ Created " . count($packages) . " credit packages\n";
                
                echo "\n🎉 DATABASE SETUP COMPLETED SUCCESSFULLY!\n\n";
                echo "✅ All required tables created\n";
                echo "✅ Admin users created\n";
                echo "✅ Basic data seeded\n\n";
                
                echo "🚀 You can now:\n";
                echo "1. Login as admin: vpersonmail@gmail.com / admin123\n";
                echo "2. Or login as: admin@legozo.com / admin123\n";
                echo "3. Test the API endpoints\n";
                echo "4. Access the full admin dashboard\n\n";
                
                // Verify the setup
                $finalTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo "Final table count: " . count($finalTables) . "\n";
                echo "Tables created: " . implode(', ', $finalTables) . "\n";
                
            } else {
                echo "⚠️ Database already has tables. Current tables:\n";
                foreach ($tables as $table) {
                    echo "  - $table\n";
                }
                echo "\nIf you need to recreate tables, please drop them first or run the installer again.\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Could not read database configuration from .env file\n";
    }
} else {
    echo "❌ .env file not found\n";
}

echo "</pre>";
echo "<div style='margin-top:20px;'>";
echo "<a href='/debug.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔍 Debug Info</a> ";
echo "<a href='/health_check.php' style='background:#10b981;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>🏥 Health Check</a> ";
echo "<a href='/admin_interface.php' style='background:#8b5cf6;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>🎛️ Admin Interface</a>";
echo "</div>";
?>