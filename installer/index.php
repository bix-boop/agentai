<?php
session_start();

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
    
    // Check directory permissions
    $directories = [
        '../backend/storage',
        '../backend/bootstrap/cache',
        '../backend/public/storage',
        '../frontend/build',
    ];
    
    foreach ($directories as $dir) {
        if (!is_writable($dir)) {
            $errors[] = "Directory '{$dir}' must be writable.";
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
    
    file_put_contents('../backend/.env', $envContent);
}

function runMigrations() {
    chdir('../backend');
    exec('php artisan migrate --force 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('Database migration failed: ' . implode('\n', $output));
    }
}

function createAdminUser() {
    $app = $_SESSION['app_config'];
    chdir('../backend');
    
    $command = sprintf(
        'php artisan tinker --execute="App\Models\User::create([\'name\' => \'%s\', \'email\' => \'%s\', \'password\' => bcrypt(\'%s\'), \'role\' => \'admin\', \'email_verified_at\' => now(), \'credits_balance\' => 100000]);"',
        addslashes($app['admin_name']),
        addslashes($app['admin_email']),
        addslashes($app['admin_password'])
    );
    
    exec($command . ' 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('Admin user creation failed: ' . implode('\n', $output));
    }
}

function setupDefaultData() {
    chdir('../backend');
    exec('php artisan db:seed --force 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('Default data setup failed: ' . implode('\n', $output));
    }
}

function buildFrontend() {
    chdir('../frontend');
    exec('npm install 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('Frontend dependency installation failed: ' . implode('\n', $output));
    }
    
    exec('npm run build 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('Frontend build failed: ' . implode('\n', $output));
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