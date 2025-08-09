<?php
echo "<h1>Phoenix AI - Manual Migration Runner</h1>";

// Check if .env exists
$envFile = __DIR__ . '/backend/.env';
if (!file_exists($envFile)) {
    die("❌ .env file not found. Please run the installer first.");
}

echo "<p>✅ .env file found</p>";

// Change to backend directory
$backendPath = __DIR__ . '/backend';
if (!is_dir($backendPath)) {
    die("❌ Backend directory not found");
}

echo "<p>✅ Backend directory found</p>";

// Check artisan
if (!file_exists($backendPath . '/artisan')) {
    die("❌ Laravel artisan not found");
}

echo "<p>✅ Laravel artisan found</p>";

// Test Laravel
echo "<h2>Testing Laravel...</h2>";
chdir($backendPath);

$output = shell_exec("php artisan --version 2>&1");
echo "<p><strong>Laravel Version:</strong> " . htmlspecialchars($output) . "</p>";

// Test database connection
echo "<h2>Testing Database Connection...</h2>";
$output = shell_exec("php artisan migrate:status 2>&1");
echo "<p><strong>Migration Status:</strong></p>";
echo "<pre style='background:#f5f5f5;padding:10px;'>" . htmlspecialchars($output) . "</pre>";

// Run migrations
echo "<h2>Running Migrations...</h2>";
$output = shell_exec("php artisan migrate --force --verbose 2>&1");
echo "<p><strong>Migration Output:</strong></p>";
echo "<pre style='background:#f5f5f5;padding:10px;'>" . htmlspecialchars($output) . "</pre>";

// Check final status
echo "<h2>Final Status Check...</h2>";
$output = shell_exec("php artisan migrate:status 2>&1");
echo "<p><strong>Final Migration Status:</strong></p>";
echo "<pre style='background:#f5f5f5;padding:10px;'>" . htmlspecialchars($output) . "</pre>";

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li><a href='/installer/'>Back to Installer</a></li>";
echo "<li><a href='/debug.php'>Check Debug Info</a></li>";
echo "<li><a href='/'>Go to Main Site</a></li>";
echo "</ul>";
?>