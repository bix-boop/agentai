<?php
// Debug what the .env file actually contains
echo "<h1>🔍 Environment Debug</h1>";
echo "<pre>";

$envFile = __DIR__ . '/backend/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    
    preg_match('/DB_HOST=(.*)/', $env, $host);
    preg_match('/DB_DATABASE=(.*)/', $env, $db);
    preg_match('/DB_USERNAME=(.*)/', $env, $user);
    preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
    
    echo "Raw matches from .env file:\n";
    echo "DB_HOST match: " . var_export($host, true) . "\n";
    echo "DB_DATABASE match: " . var_export($db, true) . "\n";
    echo "DB_USERNAME match: " . var_export($user, true) . "\n";
    echo "DB_PASSWORD match: " . var_export($pass, true) . "\n\n";
    
    if (isset($host[1], $db[1], $user[1], $pass[1])) {
        $h = $host[1];
        $d = $db[1];
        $u = $user[1];
        $p = $pass[1];
        
        echo "Extracted values:\n";
        echo "Host: '$h'\n";
        echo "Database: '$d'\n";
        echo "Username: '$u'\n";
        echo "Password: '$p' (length: " . strlen($p) . ")\n\n";
        
        echo "Testing connection with these exact values...\n";
        try {
            $pdo = new PDO("mysql:host={$h};dbname={$d}", $u, $p);
            echo "✅ CONNECTION SUCCESSFUL!\n";
            
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "Tables found: " . count($tables) . "\n";
            foreach ($tables as $table) {
                echo "  - $table\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ CONNECTION FAILED: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Could not extract all database values from .env\n";
    }
} else {
    echo "❌ .env file not found\n";
}

echo "</pre>";
?>