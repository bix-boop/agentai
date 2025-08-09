<?php
/**
 * Phoenix AI Platform Optimizer
 * Fixes common issues and optimizes the platform for production
 */

// Include utilities
require_once __DIR__ . '/installer/includes/php_utils.php';
require_once __DIR__ . '/installer/includes/error_handler.php';

// Initialize error handling
ErrorHandler::init();

echo "<!DOCTYPE html>";
echo "<html><head><title>Phoenix AI - Platform Optimizer</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .step { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #007bff; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .btn { padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
</style></head><body>";

echo "<h1>🔧 Phoenix AI - Platform Optimizer</h1>";
echo "<p>This tool fixes common issues and optimizes your Phoenix AI installation.</p>";

$optimizations = [];
$errors = [];

// Optimization 1: Fix Directory Structure
echo "<div class='step'>";
echo "<h2>📁 Fixing Directory Structure</h2>";

$directories = [
    __DIR__ . '/backend/storage/logs',
    __DIR__ . '/backend/storage/app/public',
    __DIR__ . '/backend/storage/app/public/avatars',
    __DIR__ . '/backend/storage/app/public/packages',
    __DIR__ . '/backend/storage/framework/cache',
    __DIR__ . '/backend/storage/framework/sessions',
    __DIR__ . '/backend/storage/framework/views',
    __DIR__ . '/backend/bootstrap/cache',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p class='success'>✅ Created directory: " . htmlspecialchars($dir) . "</p>";
            $optimizations[] = "Created directory: $dir";
        } else {
            echo "<p class='error'>❌ Failed to create directory: " . htmlspecialchars($dir) . "</p>";
            $errors[] = "Failed to create directory: $dir";
        }
    } else {
        echo "<p class='info'>ℹ️ Directory exists: " . htmlspecialchars(basename($dir)) . "</p>";
    }
}

echo "</div>";

// Optimization 2: Fix File Permissions
echo "<div class='step'>";
echo "<h2>🔐 Fixing File Permissions</h2>";

$permissionFixes = [
    __DIR__ . '/backend/storage' => 0755,
    __DIR__ . '/backend/bootstrap/cache' => 0755,
    __DIR__ . '/installer' => 0755,
];

foreach ($permissionFixes as $path => $permission) {
    if (is_dir($path)) {
        if (chmod($path, $permission)) {
            echo "<p class='success'>✅ Fixed permissions for: " . htmlspecialchars(basename($path)) . "</p>";
            $optimizations[] = "Fixed permissions for: $path";
        } else {
            echo "<p class='warning'>⚠️ Could not change permissions for: " . htmlspecialchars($path) . "</p>";
        }
    }
}

echo "</div>";

// Optimization 3: Validate Configuration Files
echo "<div class='step'>";
echo "<h2>⚙️ Validating Configuration</h2>";

$configFiles = [
    __DIR__ . '/backend/.env.example' => 'Environment template',
    __DIR__ . '/backend/config/phoenix.php' => 'Phoenix configuration',
    __DIR__ . '/backend/routes/api.php' => 'API routes',
    __DIR__ . '/backend/bootstrap/app.php' => 'Laravel bootstrap',
];

foreach ($configFiles as $file => $desc) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $desc exists</p>";
    } else {
        echo "<p class='error'>❌ Missing: $desc</p>";
        $errors[] = "Missing configuration file: $file";
    }
}

echo "</div>";

// Optimization 4: Test PHP CLI and Laravel
echo "<div class='step'>";
echo "<h2>🐘 Testing PHP CLI and Laravel</h2>";

try {
    $phpPath = PHPUtils::detectPHPPath();
    echo "<p class='success'>✅ PHP CLI detected: " . htmlspecialchars($phpPath) . "</p>";
    
    $backendPath = __DIR__ . '/backend';
    if (file_exists($backendPath . '/artisan')) {
        try {
            $output = PHPUtils::execArtisan("--version", $backendPath);
            if (strpos($output, 'Laravel Framework') !== false || strpos($output, 'Laravel') !== false) {
                echo "<p class='success'>✅ Laravel working: " . htmlspecialchars(trim($output)) . "</p>";
                $optimizations[] = "Laravel functionality verified";
            } else {
                echo "<p class='error'>❌ Laravel not responding properly</p>";
                $errors[] = "Laravel not responding properly";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Laravel test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            $errors[] = "Laravel test failed: " . $e->getMessage();
        }
    } else {
        echo "<p class='error'>❌ Laravel artisan not found</p>";
        $errors[] = "Laravel artisan not found";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ PHP CLI issue: " . htmlspecialchars($e->getMessage()) . "</p>";
    $errors[] = "PHP CLI issue: " . $e->getMessage();
}

echo "</div>";

// Optimization 5: Create Missing Files
echo "<div class='step'>";
echo "<h2>📝 Creating Missing Files</h2>";

// Create .gitignore if missing
$gitignorePath = __DIR__ . '/.gitignore';
if (!file_exists($gitignorePath)) {
    $gitignoreContent = "# Phoenix AI - Git Ignore Rules
/backend/.env
/backend/vendor/
/backend/node_modules/
/backend/storage/logs/*
!/backend/storage/logs/.gitkeep
/backend/storage/framework/cache/*
!/backend/storage/framework/cache/.gitkeep
/backend/storage/framework/sessions/*
!/backend/storage/framework/sessions/.gitkeep
/backend/storage/framework/views/*
!/backend/storage/framework/views/.gitkeep
/frontend/node_modules/
/frontend/build/
/frontend/dist/
*.log
.DS_Store
Thumbs.db
installer_debug.log
";
    
    if (file_put_contents($gitignorePath, $gitignoreContent)) {
        echo "<p class='success'>✅ Created .gitignore file</p>";
        $optimizations[] = "Created .gitignore file";
    } else {
        echo "<p class='error'>❌ Failed to create .gitignore file</p>";
    }
} else {
    echo "<p class='info'>ℹ️ .gitignore file exists</p>";
}

// Create storage .gitkeep files
$gitkeepFiles = [
    __DIR__ . '/backend/storage/logs/.gitkeep',
    __DIR__ . '/backend/storage/framework/cache/.gitkeep',
    __DIR__ . '/backend/storage/framework/sessions/.gitkeep',
    __DIR__ . '/backend/storage/framework/views/.gitkeep',
];

foreach ($gitkeepFiles as $file) {
    if (!file_exists($file)) {
        if (touch($file)) {
            echo "<p class='success'>✅ Created: " . htmlspecialchars(str_replace(__DIR__, '', $file)) . "</p>";
        }
    }
}

echo "</div>";

// Optimization 6: Security Enhancements
echo "<div class='step'>";
echo "<h2>🔒 Security Enhancements</h2>";

// Check .htaccess file
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo "<p class='success'>✅ .htaccess file exists</p>";
    
    $htaccessContent = file_get_contents($htaccessPath);
    if (strpos($htaccessContent, 'RewriteEngine On') !== false) {
        echo "<p class='success'>✅ URL rewriting enabled</p>";
    } else {
        echo "<p class='warning'>⚠️ URL rewriting may not be configured</p>";
    }
} else {
    echo "<p class='warning'>⚠️ .htaccess file missing</p>";
}

// Check backend .env permissions
$envPath = __DIR__ . '/backend/.env';
if (file_exists($envPath)) {
    $perms = fileperms($envPath) & 0777;
    if ($perms <= 0644) {
        echo "<p class='success'>✅ .env file permissions secure</p>";
    } else {
        echo "<p class='warning'>⚠️ .env file permissions too open (should be 644 or less)</p>";
        if (chmod($envPath, 0644)) {
            echo "<p class='success'>✅ Fixed .env file permissions</p>";
            $optimizations[] = "Fixed .env file permissions";
        }
    }
}

echo "</div>";

// Summary
echo "<div class='step'>";
echo "<h2>📊 Optimization Summary</h2>";

if (empty($errors)) {
    echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h3 style='color:#155724;margin:0;'>🎉 Optimization Complete!</h3>";
    echo "<p>Phoenix AI has been optimized and is ready for use.</p>";
    
    if (!empty($optimizations)) {
        echo "<p><strong>Optimizations applied:</strong></p>";
        echo "<ul>";
        foreach ($optimizations as $opt) {
            echo "<li>" . htmlspecialchars($opt) . "</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h3 style='color:#721c24;margin:0;'>⚠️ Issues Found</h3>";
    echo "<p>Some issues were found during optimization:</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    
    if (!empty($optimizations)) {
        echo "<p><strong>Successful optimizations:</strong></p>";
        echo "<ul>";
        foreach ($optimizations as $opt) {
            echo "<li>" . htmlspecialchars($opt) . "</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
}

echo "<div style='margin-top:30px;'>";
echo "<a href='/health_check.php' class='btn btn-primary'>🏥 Run Health Check</a>";
echo "<a href='/installer/' class='btn btn-success'>🚀 Run Installation</a>";
echo "<a href='/' class='btn btn-primary'>🏠 Go to Site</a>";
echo "</div>";

echo "</div>";

echo "</body></html>";
?>