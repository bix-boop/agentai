<?php
session_start();

// Include PHP utilities
require_once __DIR__ . '/includes/php_utils.php';

// Installation configuration
define('INSTALLER_VERSION', '1.0.0');
define('MIN_PHP_VERSION', '8.1');
define('REQUIRED_EXTENSIONS', [
    'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'gd', 
    'json', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 
    'tokenizer', 'xml', 'zip'
]);

// Get current step
$step = $_GET['step'] ?? 1;
$step = max(1, min(6, intval($step)));

// Handle form submissions and step progression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($step) {
            case 1:
                // Welcome step - just move to next
                header('Location: ?step=2');
                exit;
            case 2:
                handleRequirementsCheck();
                break;
            case 3:
                handleDatabaseSetup();
                break;
            case 4:
                handleApplicationSetup();
                break;
            case 5:
                // This step handles its own output, don't redirect
                break;
        }
    } catch (Exception $e) {
        $_SESSION['installer_error'] = $e->getMessage();
    }
}

function handleRequirementsCheck() {
    $errors = [];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
        $errors[] = "PHP version " . MIN_PHP_VERSION . " or higher is required. Current version: " . PHP_VERSION;
    }
    
    // Check required extensions
    foreach (REQUIRED_EXTENSIONS as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = "PHP extension '{$extension}' is required but not installed.";
        }
    }
    
    // Check directory permissions (recursive)
    $directories = [
        '../backend/storage',
        '../backend/bootstrap/cache',
        '../backend/public',
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            $errors[] = "Directory '{$dir}' not found.";
            continue;
        }
        if (!is_writable($dir)) {
            $errors[] = "Directory '{$dir}' must be writable.";
        }
        // Simple recursive write test
        $testFile = rtrim($dir, '/') . '/.perm_test_' . uniqid() . '.tmp';
        if (@file_put_contents($testFile, 'ok') === false) {
            $errors[] = "Directory '{$dir}' is not writable by PHP process.";
        } else {
            @unlink($testFile);
        }
    }
    
    if (empty($errors)) {
        $_SESSION['requirements_passed'] = true;
        header('Location: ?step=3');
        exit;
    } else {
        $_SESSION['requirements_errors'] = $errors;
    }
}

function handleDatabaseSetup() {
    $host = $_POST['db_host'] ?? '';
    $port = $_POST['db_port'] ?? '3306';
    $database = $_POST['db_name'] ?? '';
    $username = $_POST['db_username'] ?? '';
    $password = $_POST['db_password'] ?? '';
    
    $errors = [];
    
    if (empty($host)) $errors[] = "Database host is required.";
    if (empty($database)) $errors[] = "Database name is required.";
    if (empty($username)) $errors[] = "Database username is required.";
    
    if (empty($errors)) {
        // Test database connection
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            $_SESSION['database_config'] = [
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
            ];
            
            header('Location: ?step=4');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Database connection failed: " . $e->getMessage();
        }
    }
    
    $_SESSION['database_errors'] = $errors;
    $_SESSION['database_input'] = $_POST;
}

function handleApplicationSetup() {
    $site_name = $_POST['site_name'] ?? '';
    $site_url = $_POST['site_url'] ?? '';
    $admin_name = $_POST['admin_name'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $openai_api_key = $_POST['openai_api_key'] ?? '';
    
    $errors = [];
    
    if (empty($site_name)) $errors[] = "Site name is required.";
    if (empty($site_url)) $errors[] = "Site URL is required.";
    if (empty($admin_name)) $errors[] = "Admin name is required.";
    if (empty($admin_email)) $errors[] = "Admin email is required.";
    if (empty($admin_password)) $errors[] = "Admin password is required.";
    
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Admin email must be a valid email address.";
    }
    
    if (strlen($admin_password) < 8) {
        $errors[] = "Admin password must be at least 8 characters long.";
    }
    
    if (empty($errors)) {
        // Merge database and app config for step 5
        $dbConfig = $_SESSION['database_config'] ?? [];
        $_SESSION['installer_config'] = [
            'site_name' => $site_name,
            'site_url' => rtrim($site_url, '/'),
            'admin_name' => $admin_name,
            'admin_email' => $admin_email,
            'admin_password' => $admin_password,
            'openai_api_key' => $openai_api_key,
            'db_host' => $dbConfig['host'] ?? '',
            'db_port' => $dbConfig['port'] ?? '3306',
            'db_name' => $dbConfig['database'] ?? '',
            'db_username' => $dbConfig['username'] ?? '',
            'db_password' => $dbConfig['password'] ?? '',
        ];
        
        // Also create .env file now
        generateEnvFile();
        
        header('Location: ?step=5');
        exit;
    }
    
    $_SESSION['app_errors'] = $errors;
    $_SESSION['app_input'] = $_POST;
}

function handleFinalInstallation() {
    if (!isset($_SESSION['requirements_passed']) || !isset($_SESSION['database_config']) || !isset($_SESSION['app_config'])) {
        header('Location: ?step=1');
        exit;
    }
    
    try {
        // Generate .env file
        generateEnvFile();
        
        // Run database migrations
        runMigrations();
        
        // Create admin user
        createAdminUser();
        
        // Set up default data
        setupDefaultData();
        
        // Post install: storage link and config cache
        postInstallTasks();
        
        // Build frontend
        buildFrontend();
        
        $_SESSION['installation_complete'] = true;
        header('Location: ?step=6');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['installation_error'] = $e->getMessage();
    }
}

function generateEnvFile() {
    $config = $_SESSION['installer_config'];
    
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
    
    $backendPath = dirname(__DIR__) . '/backend';
    $envPath = $backendPath . '/.env';
    
    if (!is_dir($backendPath)) {
        throw new Exception("Backend directory not found at: " . $backendPath);
    }
    
    if (!is_writable(dirname($envPath))) {
        throw new Exception("Cannot write to backend directory. Check permissions: " . dirname($envPath));
    }
    
    $result = file_put_contents($envPath, $envContent);
    if ($result === false) {
        throw new Exception("Failed to write .env file to: " . $envPath);
    }
}

function runMigrations() {
    $backendPath = dirname(__DIR__) . '/backend';
    
    if (!is_dir($backendPath)) {
        throw new Exception("Backend directory not found at: " . $backendPath);
    }
    
    if (!file_exists($backendPath . '/artisan')) {
        throw new Exception("Laravel artisan not found at: " . $backendPath . '/artisan');
    }
    
    try {
        $output = PHPUtils::execArtisan("migrate --force", $backendPath);
        
        if (strpos($output, 'Migrated:') === false && strpos($output, 'Nothing to migrate') === false) {
            throw new Exception('Database migration failed: ' . $output);
        }
        
    } catch (Exception $e) {
        throw new Exception('Database migration failed: ' . $e->getMessage());
    }
}

function createAdminUser() {
    $config = $_SESSION['installer_config'];
    $backendPath = dirname(__DIR__) . '/backend';
    
    // Use direct database insertion instead of artisan tinker for better compatibility
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
        
    } catch (PDOException $e) {
        throw new Exception('Admin user creation failed: ' . $e->getMessage());
    }
}

function setupDefaultData() {
    $backendPath = dirname(__DIR__) . '/backend';
    
    // Use direct database operations instead of seeder for better compatibility
    try {
        $config = $_SESSION['installer_config'];
        $pdo = new PDO(
            "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}",
            $config['db_username'],
            $config['db_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Create default categories
        $categories = [
            ['Business', 'business', 'Expert business advice and consulting', '#3B82F6'],
            ['Technology', 'technology', 'Technology support and programming help', '#10B981'],
            ['Creative', 'creative', 'Creative writing and artistic assistance', '#F59E0B'],
            ['Education', 'education', 'Learning support and tutoring', '#8B5CF6'],
            ['Health', 'health', 'Health and wellness guidance', '#EF4444'],
            ['Legal', 'legal', 'Legal advice and document assistance', '#6B7280']
        ];
        
        foreach ($categories as $index => $category) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO categories (name, slug, description, color, is_active, show_on_homepage, sort_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, 1, ?, NOW(), NOW())
            ");
            $stmt->execute([$category[0], $category[1], $category[2], $category[3], $index + 1]);
        }
        
        // Create default credit packages
        $packages = [
            ['Starter Pack', '10,000 credits perfect for getting started', 10000, 999, 'USD', 1, false],
            ['Professional Pack', '50,000 credits for regular users', 50000, 2999, 'USD', 2, true],
            ['Business Pack', '150,000 credits for power users', 150000, 7999, 'USD', 3, false],
            ['Enterprise Pack', '500,000 credits for businesses', 500000, 19999, 'USD', 4, false]
        ];
        
        foreach ($packages as $index => $package) {
            $features = json_encode(['Access to all AI assistants', 'Image generation', 'Voice features', 'Priority support']);
            
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO credit_packages (name, description, credits, price_cents, currency, tier, features, is_popular, is_active, sort_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
            ");
            $stmt->execute([$package[0], $package[1], $package[2], $package[3], $package[4], $package[5], $features, $package[6], $index + 1]);
        }
        
    } catch (PDOException $e) {
        throw new Exception('Default data setup failed: ' . $e->getMessage());
    }
}

function postInstallTasks() {
    $backendPath = dirname(__DIR__) . '/backend';
    try {
        // Storage link
        PHPUtils::execArtisan('storage:link', $backendPath);
        // Cache config
        PHPUtils::execArtisan('config:cache', $backendPath);
        // Clear optimize caches
        PHPUtils::execArtisan('optimize:clear', $backendPath);
    } catch (Exception $e) {
        // Non-fatal: log but do not stop installation
        PHPUtils::log('Post-install tasks warning: ' . $e->getMessage());
    }
}

function buildFrontend() {
    $frontendPath = dirname(__DIR__) . '/frontend';
    $backendPublicPath = dirname(__DIR__) . '/backend/public';
    $targetPath = $backendPublicPath . '/app';
    
    // Only build if Node.js is available
    $nodeExists = !empty(shell_exec('which node 2>/dev/null'));
    if (!$nodeExists) {
        return; // Skip frontend build if Node.js not available
    }
    
    chdir($frontendPath);
    exec('npm install 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Frontend dependency installation failed: ' . implode('\n', $output));
    }
    
    exec('npm run build 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Frontend build failed: ' . implode('\n', $output));
    }
    
    // Copy build to backend/public/app
    $buildDir = $frontendPath . '/build';
    if (!is_dir($buildDir)) {
        throw new Exception('Frontend build directory not found at: ' . $buildDir);
    }
    
    if (!is_dir($targetPath)) {
        if (!mkdir($targetPath, 0755, true)) {
            throw new Exception('Failed to create target directory: ' . $targetPath);
        }
    }
    
    // Recursively copy build files
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($buildDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $destPath = $targetPath . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($destPath) && !mkdir($destPath, 0755, true)) {
                throw new Exception('Failed to create directory: ' . $destPath);
            }
        } else {
            if (!copy($item->getPathname(), $destPath)) {
                throw new Exception('Failed to copy file: ' . $item->getPathname() . ' to ' . $destPath);
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phoenix AI - Installation Wizard</title>
    <link href="assets/css/installer.css" rel="stylesheet">
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <div class="logo">🔥</div>
            <h1>Phoenix AI Installation Wizard</h1>
            <p>Version <?= INSTALLER_VERSION ?></p>
        </div>

        <div class="installer-progress">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <div class="step <?= $i <= $step ? 'active' : '' ?> <?= $i < $step ? 'completed' : '' ?>">
                    <span class="step-number"><?= $i ?></span>
                    <span class="step-title">
                        <?php
                        $titles = [
                            1 => 'Welcome',
                            2 => 'Requirements',
                            3 => 'Database',
                            4 => 'Configuration',
                            5 => 'Installation',
                            6 => 'Complete'
                        ];
                        echo $titles[$i];
                        ?>
                    </span>
                </div>
            <?php endfor; ?>
        </div>

        <div class="installer-content">
            <?php include "steps/step{$step}.php"; ?>
        </div>

        <div class="installer-footer">
            <p>&copy; <?= date('Y') ?> Phoenix AI. All rights reserved.</p>
        </div>
    </div>

    <script src="assets/js/installer.js"></script>
</body>
</html>