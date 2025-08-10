<?php
// Database Diagnosis Script - Mimics debug.php approach
echo "<h1>🔍 Database Diagnosis</h1>";
echo "<pre>";

$envFile = __DIR__ . '/backend/.env';
echo "Reading .env file: $envFile\n";

if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    
    // Extract database configuration exactly like debug.php does
    preg_match('/DB_HOST=(.*)/', $env, $host);
    preg_match('/DB_DATABASE=(.*)/', $env, $db);
    preg_match('/DB_USERNAME=(.*)/', $env, $user);
    preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
    
    echo "Regex matches:\n";
    echo "Host: " . var_export($host, true) . "\n";
    echo "Database: " . var_export($db, true) . "\n";
    echo "Username: " . var_export($user, true) . "\n";
    echo "Password: " . var_export($pass, true) . "\n\n";
    
    if (isset($host[1], $db[1], $user[1], $pass[1])) {
        $h = trim($host[1]);
        $d = trim($db[1]);
        $u = trim($user[1]);
        $p = trim($pass[1]);
        
        echo "Cleaned values:\n";
        echo "Host: '$h'\n";
        echo "Database: '$d'\n";
        echo "Username: '$u'\n";
        echo "Password: '$p' (length: " . strlen($p) . ")\n";
        echo "Password is empty: " . (empty($p) ? 'YES' : 'NO') . "\n\n";
        
        echo "Testing connection (exactly like debug.php)...\n";
        try {
            $pdo = new PDO("mysql:host={$h};dbname={$d}", $u, $p);
            echo "✅ Database connection: Connected\n";
            
            // Check if tables exist
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "Database tables: " . count($tables) . " tables found\n";
            
            if (count($tables) > 0) {
                echo "Tables:\n";
                foreach ($tables as $table) {
                    echo "  - $table\n";
                }
                
                // Check if users table exists and has admin
                if (in_array('users', $tables)) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "\nAdmin users in database: " . $result['count'] . "\n";
                    
                    if ($result['count'] > 0) {
                        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'admin' LIMIT 3");
                        $stmt->execute();
                        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($admins as $admin) {
                            echo "  - ID: {$admin['id']}, Name: {$admin['name']}, Email: {$admin['email']}\n";
                        }
                    }
                }
            } else {
                echo "\n❌ NO TABLES FOUND! Database is empty.\n";
                echo "This means the migration process failed or ran on a different database.\n";
                
                // Check if there are other databases that might have the tables
                echo "\nChecking for other databases...\n";
                $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($dbs as $database) {
                    if (strpos($database, 'phoenix') !== false || strpos($database, 'agent') !== false) {
                        echo "  Found related database: $database\n";
                        
                        // Check if this database has tables
                        try {
                            $testPdo = new PDO("mysql:host={$h};dbname={$database}", $u, $p);
                            $testTables = $testPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                            if (count($testTables) > 0) {
                                echo "    → This database has " . count($testTables) . " tables!\n";
                                foreach ($testTables as $table) {
                                    echo "      - $table\n";
                                }
                            }
                        } catch (Exception $e) {
                            echo "    → Cannot access this database\n";
                        }
                    }
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ Database connection: Failed - " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Could not extract database configuration from .env\n";
        echo "Missing values:\n";
        echo "Host isset: " . (isset($host[1]) ? 'YES' : 'NO') . "\n";
        echo "Database isset: " . (isset($db[1]) ? 'YES' : 'NO') . "\n";
        echo "Username isset: " . (isset($user[1]) ? 'YES' : 'NO') . "\n";
        echo "Password isset: " . (isset($pass[1]) ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "❌ .env file not found at: $envFile\n";
}

echo "</pre>";
echo "<p><a href='/debug.php'>← Back to Debug</a> | <a href='/'>Home</a></p>";
?>