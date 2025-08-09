<?php
echo "<h1>Phoenix AI Debug Information</h1>";
echo "<h2>System Status</h2>";

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

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li><a href='/backend/public'>Try Backend Direct Access</a></li>";
echo "<li><a href='/installer/'>Run Installer Again</a></li>";
echo "<li><a href='/frontend/public/index.html'>Try Frontend</a></li>";
echo "</ul>";
?>