<?php
/**
 * Phoenix AI - Vendor Directory Setup
 * Sets up Laravel dependencies when Composer is not available on the server
 */

// Include utilities
require_once __DIR__ . '/installer/includes/php_utils.php';
require_once __DIR__ . '/installer/includes/error_handler.php';

// Initialize error handling
ErrorHandler::init();

echo "<!DOCTYPE html>";
echo "<html><head><title>Phoenix AI - Vendor Setup</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
    .step { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #007bff; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .btn { padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-warning { background: #ffc107; color: black; }
</style></head><body>";

echo "<h1>📦 Phoenix AI - Vendor Directory Setup</h1>";

$backendPath = __DIR__ . '/backend';
$vendorPath = $backendPath . '/vendor';

echo "<div class='step'>";
echo "<h2>🔍 Checking Current Status</h2>";

if (is_dir($vendorPath)) {
    echo "<p class='success'>✅ Vendor directory exists</p>";
    
    // Check if autoload.php exists
    if (file_exists($vendorPath . '/autoload.php')) {
        echo "<p class='success'>✅ Autoloader exists</p>";
        
        // Test if Laravel can load
        try {
            require_once $vendorPath . '/autoload.php';
            echo "<p class='success'>✅ Laravel dependencies can be loaded</p>";
            
            echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
            echo "<h3 style='color:#155724;'>🎉 Dependencies Ready!</h3>";
            echo "<p>Laravel dependencies are properly installed. You can proceed with the installation.</p>";
            echo "<a href='/installer/install.php' class='btn btn-success'>🚀 Continue Installation</a>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Dependencies corrupted: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Autoloader missing</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Vendor directory missing</p>";
    
    // Check for Composer
    $composerPath = PHPUtils::checkComposer();
    if ($composerPath) {
        echo "<p class='info'>✅ Composer found at: " . htmlspecialchars($composerPath) . "</p>";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_dependencies'])) {
            echo "<h3>📥 Installing Dependencies</h3>";
            
            try {
                chdir($backendPath);
                
                echo "<p>Running: $composerPath install --no-dev --optimize-autoloader --no-interaction</p>";
                
                $output = shell_exec("$composerPath install --no-dev --optimize-autoloader --no-interaction 2>&1");
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                
                if (is_dir($vendorPath) && file_exists($vendorPath . '/autoload.php')) {
                    echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
                    echo "<h3 style='color:#155724;'>🎉 Installation Successful!</h3>";
                    echo "<p>Laravel dependencies have been installed successfully.</p>";
                    echo "<a href='/installer/install.php' class='btn btn-success'>🚀 Continue with Phoenix AI Installation</a>";
                    echo "</div>";
                } else {
                    echo "<div style='background:#f8d7da;padding:20px;border-radius:8px;margin:20px 0;'>";
                    echo "<h3 style='color:#721c24;'>❌ Installation Failed</h3>";
                    echo "<p>Composer install completed but vendor directory is still missing.</p>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Installation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;margin:20px 0;'>";
            echo "<h3 style='color:#856404;'>📦 Dependencies Need Installation</h3>";
            echo "<p>Laravel dependencies need to be installed using Composer.</p>";
            echo "<form method='POST'>";
            echo "<button type='submit' name='install_dependencies' class='btn btn-warning'>📥 Install Dependencies Now</button>";
            echo "</form>";
            echo "<p><small>This may take a few minutes depending on your server speed.</small></p>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>❌ Composer not found</p>";
        
        echo "<div style='background:#f8d7da;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3 style='color:#721c24;'>🚨 Manual Setup Required</h3>";
        echo "<p>Composer is not available on this server. You need to:</p>";
        echo "<ol>";
        echo "<li><strong>On your local machine:</strong> Run <code>composer install --no-dev</code> in the backend directory</li>";
        echo "<li><strong>Upload the vendor folder:</strong> Upload the generated <code>vendor/</code> directory to <code>/backend/vendor/</code></li>";
        echo "<li><strong>Alternative:</strong> Install Composer on your server</li>";
        echo "</ol>";
        echo "<p><strong>Or try this command on your server:</strong></p>";
        echo "<pre>cd " . htmlspecialchars($backendPath) . " && curl -sS https://getcomposer.org/installer | php && php composer.phar install --no-dev</pre>";
        echo "</div>";
        
        echo "<div style='background:#d1ecf1;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3 style='color:#0c5460;'>💡 Quick Solution</h3>";
        echo "<p>If you have shell access to your server, try these commands:</p>";
        echo "<pre>";
        echo "cd " . htmlspecialchars($backendPath) . "\n";
        echo "curl -sS https://getcomposer.org/installer | " . htmlspecialchars(PHPUtils::detectPHPPath()) . "\n";
        echo htmlspecialchars(PHPUtils::detectPHPPath()) . " composer.phar install --no-dev --optimize-autoloader\n";
        echo "</pre>";
        echo "</div>";
    }
}

echo "</div>";

// Alternative: Create minimal vendor structure
if (!is_dir($vendorPath)) {
    echo "<div class='step'>";
    echo "<h2>🔧 Alternative: Minimal Setup</h2>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_minimal'])) {
        echo "<p class='info'>Creating minimal vendor structure...</p>";
        
        try {
            // Create vendor directory structure
            mkdir($vendorPath, 0755, true);
            mkdir($vendorPath . '/composer', 0755, true);
            
            // Create minimal autoload.php
            $autoloadContent = '<?php
// Minimal autoloader for Phoenix AI
// This is a fallback when Composer is not available

spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = __DIR__ . "/../app/" . str_replace("\\\\", "/", str_replace("App\\\\", "", $class)) . ".php";
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// Load Laravel framework classes if available
$laravelAutoload = __DIR__ . "/../../vendor/autoload.php";
if (file_exists($laravelAutoload)) {
    require_once $laravelAutoload;
}

// Define basic Laravel functions if not available
if (!function_exists("app")) {
    function app($abstract = null) {
        return $abstract ? new $abstract : new stdClass();
    }
}

if (!function_exists("config")) {
    function config($key = null, $default = null) {
        return $default;
    }
}
';
            
            file_put_contents($vendorPath . '/autoload.php', $autoloadContent);
            
            echo "<p class='warning'>⚠️ Created minimal autoloader (limited functionality)</p>";
            echo "<p class='info'>This allows basic functionality but you should still install proper dependencies.</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Failed to create minimal setup: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;'>";
        echo "<h3 style='color:#856404;'>⚠️ Emergency Fallback</h3>";
        echo "<p>If you cannot install Composer, we can create a minimal vendor structure.</p>";
        echo "<p><strong>Warning:</strong> This provides limited functionality and is not recommended for production.</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='create_minimal' class='btn btn-warning'>🔧 Create Minimal Setup</button>";
        echo "</form>";
        echo "</div>";
    }
    
    echo "</div>";
}

echo "<div style='margin-top:30px;'>";
echo "<a href='/health_check.php' class='btn btn-primary'>🏥 Health Check</a>";
echo "<a href='/debug.php' class='btn btn-primary'>🔍 Debug Info</a>";
if (is_dir($vendorPath)) {
    echo "<a href='/installer/install.php' class='btn btn-success'>🚀 Continue Installation</a>";
}
echo "</div>";

echo "</body></html>";
?>