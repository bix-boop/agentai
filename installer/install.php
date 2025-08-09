<?php
/**
 * Phoenix AI - Bulletproof Installation Script
 * This script handles the complete installation process with proper error handling
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHP utilities
require_once __DIR__ . '/includes/php_utils.php';
require_once __DIR__ . '/includes/error_handler.php';

// Initialize error handling
ErrorHandler::init();

class PhoenixInstaller {
    private $backendPath;
    private $frontendPath;
    private $rootPath;
    
    public function __construct() {
        $this->rootPath = dirname(__DIR__);
        $this->backendPath = $this->rootPath . '/backend';
        $this->frontendPath = $this->rootPath . '/frontend';
    }
    
    public function install($config) {
        try {
            $this->log("🚀 Starting Phoenix AI Installation");
            $this->log("Root Path: " . $this->rootPath);
            $this->log("Backend Path: " . $this->backendPath);
            $this->log("Frontend Path: " . $this->frontendPath);
            
            // Step 1: Validate environment
            $this->validateEnvironment();
            
            // Step 2: Create .env file
            $this->createEnvFile($config);
            
            // Step 3: Setup Laravel
            $this->setupLaravel();
            
            // Step 4: Run migrations
            $this->runMigrations();
            
            // Step 5: Create admin user
            $this->createAdminUser($config);
            
            // Step 6: Seed default data
            $this->seedDefaultData();
            
            // Step 7: Setup frontend
            $this->setupFrontend();
            
            // Step 8: Final configuration
            $this->finalizeInstallation();
            
            $this->log("🎉 Installation completed successfully!");
            $this->log("<br><div style='text-align: center; margin: 20px 0;'>");
            $this->log("<a href='/verify-installation' style='display: inline-block; background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; margin: 0 8px;'>🔍 Verify Installation</a>");
            $this->log("<a href='/' style='display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; margin: 0 8px;'>🚀 Launch Phoenix AI</a>");
            $this->log("</div>");
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ Installation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function validateEnvironment() {
        $this->log("🔍 Validating environment...");
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.1', '<')) {
            throw new Exception("PHP 8.1+ required. Current: " . PHP_VERSION);
        }
        $this->log("✅ PHP version: " . PHP_VERSION);
        
        // Check required extensions
        $required = ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'gd', 'json', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'tokenizer', 'xml', 'zip'];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception("Required PHP extension missing: " . $ext);
            }
        }
        $this->log("✅ All required PHP extensions loaded");
        
        // Check directories
        if (!is_dir($this->backendPath)) {
            throw new Exception("Backend directory not found: " . $this->backendPath);
        }
        if (!file_exists($this->backendPath . '/artisan')) {
            throw new Exception("Laravel artisan not found: " . $this->backendPath . '/artisan');
        }
        $this->log("✅ Directory structure validated");
        
        // Check permissions
        if (!is_writable($this->backendPath)) {
            throw new Exception("Backend directory not writable: " . $this->backendPath);
        }
        $this->log("✅ File permissions validated");
    }
    
    private function createEnvFile($config) {
        $this->log("📝 Creating .env file...");
        
        $envContent = "APP_NAME=\"{$config['site_name']}\"
APP_ENV=production
APP_KEY=base64:" . base64_encode(random_bytes(32)) . "
APP_DEBUG=false
APP_URL={$config['site_url']}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$config['db_host']}
DB_PORT={$config['db_port']}
DB_DATABASE={$config['db_name']}
DB_USERNAME={$config['db_username']}
DB_PASSWORD={$config['db_password']}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=\"hello@example.com\"
MAIL_FROM_NAME=\"\${APP_NAME}\"

OPENAI_API_KEY={$config['openai_api_key']}

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_WEBHOOK_ID=

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME=\"\${APP_NAME}\"
VITE_PUSHER_APP_KEY=\"\${PUSHER_APP_KEY}\"
VITE_PUSHER_HOST=\"\${PUSHER_HOST}\"
VITE_PUSHER_PORT=\"\${PUSHER_PORT}\"
VITE_PUSHER_SCHEME=\"\${PUSHER_SCHEME}\"
VITE_PUSHER_APP_CLUSTER=\"\${PUSHER_APP_CLUSTER}\"
";
        
        $envPath = $this->backendPath . '/.env';
        $result = file_put_contents($envPath, $envContent);
        
        if ($result === false) {
            throw new Exception("Failed to write .env file to: " . $envPath);
        }
        
        $this->log("✅ .env file created successfully");
        
        // Test database connection
        try {
            $pdo = new PDO(
                "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}",
                $config['db_username'],
                $config['db_password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->log("✅ Database connection verified");
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function setupLaravel() {
        $this->log("⚙️ Setting up Laravel...");
        
        try {
            // Detect PHP path first
            $phpPath = PHPUtils::detectPHPPath();
            $this->log("✅ PHP CLI detected at: " . $phpPath);
            
            chdir($this->backendPath);
            
            // Check if vendor directory exists
            if (!is_dir($this->backendPath . '/vendor')) {
                $this->log("📦 Installing Laravel dependencies...");
                
                // Check if Composer is available
                $composerPath = PHPUtils::checkComposer();
                if ($composerPath) {
                    $this->log("✅ Composer found at: " . $composerPath);
                    
                    // Install dependencies
                    $output = $this->runCommand("$composerPath install --no-dev --optimize-autoloader --no-interaction", "Composer install");
                    $this->log("Composer output: " . substr($output, 0, 500) . (strlen($output) > 500 ? '...' : ''));
                    
                    if (!is_dir($this->backendPath . '/vendor')) {
                        throw new Exception("Composer install failed - vendor directory still missing");
                    }
                    
                    $this->log("✅ Laravel dependencies installed");
                } else {
                    throw new Exception("Composer not found and vendor directory missing. Please visit /setup_vendor.php to install dependencies or upload the vendor directory manually.");
                }
            } else {
                $this->log("✅ Laravel dependencies already installed");
            }
            
            // Clear config and route caches (safe to do before migrations)
            $this->runArtisanCommand("config:clear", "Config cache cleared");
            $this->runArtisanCommand("route:clear", "Route cache cleared");
            $this->runArtisanCommand("view:clear", "View cache cleared");
            
            // Generate application key
            $this->runArtisanCommand("key:generate --force", "Application key generated");
            
            // Test Laravel
            $output = $this->runArtisanCommand("--version", "Laravel version check", false);
            if (strpos($output, 'Laravel Framework') === false && strpos($output, 'Laravel') === false) {
                throw new Exception("Laravel not working properly. Output: " . $output);
            }
            
            $this->log("✅ Laravel setup completed");
            
        } catch (Exception $e) {
            throw new Exception("Laravel setup failed: " . $e->getMessage());
        }
    }
    
    private function runMigrations() {
        $this->log("🔄 Running database migrations...");
        
        try {
            chdir($this->backendPath);
            
            // First check migration status
            $output = $this->runArtisanCommand("migrate:status", "Migration status check", false);
            $this->log("Migration status: " . trim($output));
            
            // Run migrations
            $output = $this->runArtisanCommand("migrate --force --verbose", "Database migrations");
            
            // Check for successful migration indicators
            $hasErrors = strpos($output, 'ERROR') !== false || strpos($output, 'SQLSTATE') !== false;
            $hasSuccess = (
                strpos($output, 'Migrated:') !== false ||
                strpos($output, 'Nothing to migrate') !== false ||
                strpos($output, 'DONE') !== false ||
                strpos($output, 'INFO Running migrations') !== false
            );
            
            if ($hasErrors || !$hasSuccess) {
                throw new Exception("Migration may have failed. Output: " . $output);
            }
            
            $this->log("✅ Database migrations completed");
            
            // Now safe to clear application cache (after database tables exist)
            try {
                $this->runArtisanCommand("cache:clear", "Application cache cleared", false);
                $this->log("✅ Application cache cleared");
            } catch (Exception $e) {
                $this->log("⚠️ Cache clear warning: " . $e->getMessage());
                // Don't fail installation for cache issues
            }
            
        } catch (Exception $e) {
            throw new Exception("Migration failed: " . $e->getMessage());
        }
    }
    
    private function createAdminUser($config) {
        $this->log("👤 Creating admin user...");
        
        chdir($this->backendPath);
        
        // Use direct database insertion instead of artisan tinker
        try {
            $pdo = new PDO(
                "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}",
                $config['db_username'],
                $config['db_password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $hashedPassword = password_hash($config['admin_password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, is_active, is_verified, credits_balance, email_verified_at, created_at, updated_at)
                VALUES (?, ?, ?, 'admin', 1, 1, 100000, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                password = VALUES(password),
                updated_at = NOW()
            ");
            
            $stmt->execute([$config['admin_name'], $config['admin_email'], $hashedPassword]);
            $this->log("✅ Admin user created: " . $config['admin_email']);
            
        } catch (PDOException $e) {
            throw new Exception("Failed to create admin user: " . $e->getMessage());
        }
    }
    
    private function seedDefaultData() {
        $this->log("🌱 Seeding default data...");
        
        chdir($this->backendPath);
        
        try {
            // Use Laravel seeders for proper data seeding
            $output = $this->runArtisanCommand("db:seed --force", "Database seeding", false);
            
            // Check for critical errors that should stop installation
            $hasCriticalErrors = (
                strpos($output, 'SQLSTATE[42S02]') !== false || // Table doesn't exist
                strpos($output, 'Class ') !== false && strpos($output, ' not found') !== false || // Missing class
                strpos($output, 'Syntax error') !== false // PHP syntax error
            );
            
            if ($hasCriticalErrors) {
                throw new Exception("Critical seeding error. Output: " . $output);
            }
            
            // Check for non-critical errors (column mismatches, etc.)
            if (strpos($output, 'SQLSTATE') !== false || strpos($output, 'ERROR') !== false) {
                $this->log("⚠️ Seeding completed with warnings: " . substr($output, 0, 200) . "...");
                $this->log("✅ Core data seeded (some optional data may be skipped)");
            } else {
                $this->log("✅ Default data seeded successfully");
            }
            
        } catch (Exception $e) {
            // Try to continue with minimal seeding if seeders fail
            $this->log("⚠️ Seeder failed, attempting minimal setup...");
            
            try {
                // At minimum, ensure we have basic settings
                $this->runArtisanCommand("tinker --execute=\"App\\Models\\Setting::set('site_name', 'Phoenix AI'); App\\Models\\Setting::set('welcome_credits', 1000);\"", "Basic settings", false);
                $this->log("✅ Minimal configuration applied");
            } catch (Exception $minimalError) {
                // Even minimal setup failed, but don't stop installation
                $this->log("⚠️ Seeding skipped - you can add data manually via admin panel");
            }
        }
    }
    
        private function setupFrontend() {
        $this->log("🏗️ Setting up frontend...");
        
        // Check if Node.js is available
        $nodeExists = !empty(shell_exec('which node 2>/dev/null'));
        
        if ($nodeExists) {
            $this->log("Node.js detected - building frontend...");
            
            if (is_dir($this->frontendPath)) {
                chdir($this->frontendPath);
                
                $this->runCommand("npm install --silent", "Frontend dependencies");
                $this->runCommand("npm run build", "Frontend build");
                
                $this->log("✅ Frontend built successfully");
            } else {
                $this->log("⚠️ Frontend directory not found, skipping build");
            }
        } else {
            $this->log("✅ Using pre-built frontend files (Node.js not required)");                                                                            
        }
    }
    
    private function finalizeInstallation() {
        $this->log("🎯 Finalizing installation...");
        
        try {
            chdir($this->backendPath);
            
            // Cache configuration for production
            $this->runArtisanCommand("config:cache", "Config cached");
            $this->runArtisanCommand("route:cache", "Routes cached");
            
            $this->log("✅ Installation finalized");
            
        } catch (Exception $e) {
            $this->log("⚠️ Finalization warning: " . $e->getMessage());
            // Don't fail installation for caching issues
        }
    }
    
    private function runArtisanCommand($command, $description, $throwOnError = true) {
        $this->log("Running artisan: " . $command);
        
        try {
            $output = PHPUtils::execArtisan($command, $this->backendPath);
            $this->log("Output: " . trim($output));
            
            if ($throwOnError && (strpos($output, 'Error') !== false || strpos($output, 'Exception') !== false || strpos($output, 'command not found') !== false)) {
                throw new Exception($description . " failed: " . $output);
            }
            
            return $output;
            
        } catch (Exception $e) {
            if ($throwOnError) {
                throw new Exception($description . " failed: " . $e->getMessage());
            }
            return $e->getMessage();
        }
    }
    
    private function runCommand($command, $description, $throwOnError = true) {
        $this->log("Running: " . $command);
        
        $output = shell_exec($command . " 2>&1");
        $this->log("Output: " . trim($output));
        
        if ($throwOnError && (strpos($output, 'Error') !== false || strpos($output, 'Exception') !== false || strpos($output, 'command not found') !== false)) {
            throw new Exception($description . " failed: " . $output);
        }
        
        return $output;
    }
    
    private function log($message) {
        echo "<p>" . htmlspecialchars($message) . "</p>";
        flush();
    }
}

// Handle installation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    
    $config = [
        'site_name' => $_POST['site_name'] ?? '',
        'site_url' => $_POST['site_url'] ?? '',
        'admin_name' => $_POST['admin_name'] ?? '',
        'admin_email' => $_POST['admin_email'] ?? '',
        'admin_password' => $_POST['admin_password'] ?? '',
        'openai_api_key' => $_POST['openai_api_key'] ?? '',
        'db_host' => $_POST['db_host'] ?? '',
        'db_port' => $_POST['db_port'] ?? '3306',
        'db_name' => $_POST['db_name'] ?? '',
        'db_username' => $_POST['db_username'] ?? '',
        'db_password' => $_POST['db_password'] ?? '',
    ];
    
    echo "<h1>Phoenix AI Installation</h1>";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px; max-height: 600px; overflow-y: auto;'>";
    
    try {
        $installer = new PhoenixInstaller();
        $installer->install($config);
        
        echo "</div>";
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
        echo "<h2>🎉 Installation Complete!</h2>";
        echo "<p>Phoenix AI has been successfully installed.</p>";
        echo "<ul>";
        echo "<li><a href='/'>Visit Your Site</a></li>";
        echo "<li><a href='/admin'>Admin Dashboard</a></li>";
        echo "<li><a href='/debug.php'>Debug Info</a></li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "</div>";
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
        echo "<h2>❌ Installation Failed</h2>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<button onclick='history.back()'>Go Back</button>";
        echo "</div>";
    }
    
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Phoenix AI - Quick Install</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>🔥 Phoenix AI - Quick Installation</h1>
    
    <div class="warning">
        <strong>⚠️ Important:</strong> This will overwrite your current .env file and reset the database.
    </div>
    
    <form method="post">
        <input type="hidden" name="action" value="install">
        
        <div class="section">
            <h3>🌐 Site Configuration</h3>
            <div class="form-group">
                <label>Site Name:</label>
                <input type="text" name="site_name" value="Phoenix AI" required>
            </div>
            <div class="form-group">
                <label>Site URL:</label>
                <input type="url" name="site_url" value="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] ?>" required>
            </div>
        </div>
        
        <div class="section">
            <h3>🗄️ Database Configuration</h3>
            <div class="form-group">
                <label>Database Host:</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div class="form-group">
                <label>Database Port:</label>
                <input type="number" name="db_port" value="3306" required>
            </div>
            <div class="form-group">
                <label>Database Name:</label>
                <input type="text" name="db_name" required>
            </div>
            <div class="form-group">
                <label>Database Username:</label>
                <input type="text" name="db_username" required>
            </div>
            <div class="form-group">
                <label>Database Password:</label>
                <input type="password" name="db_password">
            </div>
        </div>
        
        <div class="section">
            <h3>👤 Admin User</h3>
            <div class="form-group">
                <label>Admin Name:</label>
                <input type="text" name="admin_name" required>
            </div>
            <div class="form-group">
                <label>Admin Email:</label>
                <input type="email" name="admin_email" required>
            </div>
            <div class="form-group">
                <label>Admin Password:</label>
                <input type="password" name="admin_password" required minlength="8">
            </div>
        </div>
        
        <div class="section">
            <h3>🤖 OpenAI Configuration (Optional)</h3>
            <div class="form-group">
                <label>OpenAI API Key:</label>
                <input type="password" name="openai_api_key" placeholder="sk-proj-... or sk-...">
                <small>Leave empty to configure later in admin panel</small>
            </div>
        </div>
        
        <button type="submit">🚀 Install Phoenix AI</button>
    </form>
    
    <div style="margin-top: 30px;">
        <h3>🔗 Other Options</h3>
        <ul>
            <li><a href="index.php">Use Step-by-Step Installer</a></li>
            <li><a href="../debug.php">Debug Current Installation</a></li>
            <li><a href="../run_migrations.php">Test Migrations Only</a></li>
        </ul>
    </div>
</body>
</html>