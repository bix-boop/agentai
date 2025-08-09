<?php
/**
 * Phoenix AI - Composer Auto-Installer
 * Automatically downloads and installs Composer if missing
 */

// Include utilities
require_once __DIR__ . '/installer/includes/php_utils.php';
require_once __DIR__ . '/installer/includes/error_handler.php';

// Initialize error handling
ErrorHandler::init();

echo "<!DOCTYPE html>";
echo "<html><head><title>Phoenix AI - Composer Installer</title>";
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

echo "<h1>🎼 Phoenix AI - Composer Auto-Installer</h1>";

$backendPath = __DIR__ . '/backend';

echo "<div class='step'>";
echo "<h2>🔍 Checking Composer Status</h2>";

// Check if Composer is already available
$composerPath = PHPUtils::checkComposer();
if ($composerPath) {
    echo "<p class='success'>✅ Composer already available at: " . htmlspecialchars($composerPath) . "</p>";
    echo "<a href='/setup_vendor.php' class='btn btn-success'>📦 Install Laravel Dependencies</a>";
} else {
    echo "<p class='warning'>⚠️ Composer not found in system PATH</p>";
    
    // Check if local composer.phar exists
    $localComposer = $backendPath . '/composer.phar';
    if (file_exists($localComposer)) {
        echo "<p class='success'>✅ Local composer.phar found</p>";
        echo "<a href='/setup_vendor.php' class='btn btn-success'>📦 Install Laravel Dependencies</a>";
    } else {
        echo "<p class='info'>📥 Local composer.phar not found</p>";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_composer'])) {
            echo "<h3>📥 Installing Composer</h3>";
            
            try {
                $phpPath = PHPUtils::detectPHPPath();
                echo "<p>Using PHP: " . htmlspecialchars($phpPath) . "</p>";
                
                chdir($backendPath);
                
                // Download Composer installer
                echo "<p>Downloading Composer installer...</p>";
                $installerUrl = 'https://getcomposer.org/installer';
                $installerContent = file_get_contents($installerUrl);
                
                if ($installerContent === false) {
                    throw new Exception("Failed to download Composer installer");
                }
                
                file_put_contents('composer-setup.php', $installerContent);
                echo "<p class='success'>✅ Composer installer downloaded</p>";
                
                // Run Composer installer
                echo "<p>Installing Composer...</p>";
                $output = shell_exec("$phpPath composer-setup.php 2>&1");
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                
                // Clean up installer
                unlink('composer-setup.php');
                
                if (file_exists($localComposer)) {
                    echo "<p class='success'>✅ Composer installed successfully!</p>";
                    
                    // Now install Laravel dependencies
                    echo "<h3>📦 Installing Laravel Dependencies</h3>";
                    $output = shell_exec("$phpPath composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1");
                    echo "<pre>" . htmlspecialchars($output) . "</pre>";
                    
                    if (is_dir($backendPath . '/vendor')) {
                        echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
                        echo "<h3 style='color:#155724;'>🎉 Complete Success!</h3>";
                        echo "<p>Composer and Laravel dependencies installed successfully!</p>";
                        echo "<a href='/installer/install.php' class='btn btn-success'>🚀 Continue with Phoenix AI Installation</a>";
                        echo "</div>";
                    } else {
                        echo "<p class='error'>❌ Laravel dependencies installation failed</p>";
                    }
                } else {
                    echo "<p class='error'>❌ Composer installation failed</p>";
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Installation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<div style='background:#d1ecf1;padding:20px;border-radius:8px;margin:20px 0;'>";
            echo "<h3 style='color:#0c5460;'>🎼 Auto-Install Composer</h3>";
            echo "<p>We can automatically download and install Composer for you.</p>";
            echo "<form method='POST'>";
            echo "<button type='submit' name='install_composer' class='btn btn-primary'>🎼 Download & Install Composer</button>";
            echo "</form>";
            echo "<p><small>This will download Composer from getcomposer.org and install Laravel dependencies.</small></p>";
            echo "</div>";
            
            echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;margin:20px 0;'>";
            echo "<h3 style='color:#856404;'>📋 Manual Installation Instructions</h3>";
            echo "<p>If automatic installation doesn't work, follow these steps:</p>";
            echo "<ol>";
            echo "<li>SSH into your server</li>";
            echo "<li>Navigate to: <code>" . htmlspecialchars($backendPath) . "</code></li>";
            echo "<li>Run: <code>curl -sS https://getcomposer.org/installer | " . htmlspecialchars(PHPUtils::detectPHPPath()) . "</code></li>";
            echo "<li>Run: <code>" . htmlspecialchars(PHPUtils::detectPHPPath()) . " composer.phar install --no-dev</code></li>";
            echo "</ol>";
            echo "</div>";
        }
    }
}

echo "</div>";

echo "<div style='margin-top:30px;'>";
echo "<a href='/health_check.php' class='btn btn-primary'>🏥 Health Check</a>";
echo "<a href='/debug.php' class='btn btn-primary'>🔍 Debug Info</a>";
echo "<a href='/setup_vendor.php' class='btn btn-warning'>📦 Vendor Setup</a>";
echo "</div>";

echo "</body></html>";
?>