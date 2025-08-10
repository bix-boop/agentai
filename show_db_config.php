<?php
// Show exact database configuration being read
echo "<h1>🔍 Database Configuration Debug</h1>";
echo "<pre>";

$envFile = __DIR__ . '/backend/.env';
echo "Reading .env file: $envFile\n\n";

if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    
    // Show relevant lines from .env
    echo "Relevant .env lines:\n";
    $lines = explode("\n", $env);
    foreach ($lines as $line) {
        if (strpos($line, 'DB_') === 0) {
            if (strpos($line, 'DB_PASSWORD') === 0) {
                echo $line . " (length: " . strlen(substr($line, 12)) . ")\n";
            } else {
                echo $line . "\n";
            }
        }
    }
    
    echo "\nRegex extraction (like debug.php):\n";
    preg_match('/DB_HOST=(.*)/', $env, $host);
    preg_match('/DB_DATABASE=(.*)/', $env, $db);
    preg_match('/DB_USERNAME=(.*)/', $env, $user);
    preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
    
    echo "Host raw: " . var_export($host, true) . "\n";
    echo "Database raw: " . var_export($db, true) . "\n";
    echo "Username raw: " . var_export($user, true) . "\n";
    echo "Password raw: " . var_export($pass, true) . "\n\n";
    
    if (isset($host[1], $db[1], $user[1], $pass[1])) {
        $h = $host[1];
        $d = $db[1];
        $u = $user[1];
        $p = $pass[1];
        
        echo "Extracted values:\n";
        echo "Host: '$h'\n";
        echo "Database: '$d'\n";
        echo "Username: '$u'\n";
        echo "Password: '$p' (length: " . strlen($p) . ", empty: " . (empty($p) ? 'YES' : 'NO') . ")\n\n";
        
        // Test connection exactly like debug.php
        echo "Testing connection (debug.php method)...\n";
        try {
            $pdo = new PDO("mysql:host={$h};dbname={$d}", $u, $p);
            echo "✅ SUCCESS: Connection works!\n";
            
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "Tables found: " . count($tables) . "\n";
            
            if (count($tables) === 0) {
                echo "\n🔍 Checking if tables exist in other databases...\n";
                
                // Connect without specifying database
                $pdo2 = new PDO("mysql:host={$h}", $u, $p);
                $databases = $pdo2->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($databases as $database) {
                    if (in_array($database, ['information_schema', 'performance_schema', 'mysql', 'sys'])) continue;
                    
                    echo "Checking database: $database\n";
                    try {
                        $pdo3 = new PDO("mysql:host={$h};dbname={$database}", $u, $p);
                        $testTables = $pdo3->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                        if (count($testTables) > 0) {
                            echo "  → Found " . count($testTables) . " tables in '$database':\n";
                            foreach ($testTables as $table) {
                                echo "    - $table\n";
                            }
                            
                            // Check if this looks like our Phoenix AI database
                            $phoenixTables = ['users', 'ai_assistants', 'categories', 'chats', 'messages'];
                            $foundPhoenixTables = array_intersect($phoenixTables, $testTables);
                            if (count($foundPhoenixTables) > 0) {
                                echo "  🎯 This looks like the Phoenix AI database!\n";
                            }
                        }
                    } catch (Exception $e) {
                        echo "  → Cannot access: " . $e->getMessage() . "\n";
                    }
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ FAILED: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Could not extract database configuration\n";
    }
} else {
    echo "❌ .env file not found\n";
}

echo "</pre>";
?>