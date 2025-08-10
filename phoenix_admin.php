<?php
// Phoenix AI Standalone Admin Interface
// Works like debug.php - bypasses all routing issues

session_start();

// Simple file-based authentication
$adminFile = __DIR__ . '/phoenix_admin_session.json';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    if (file_exists($adminFile)) unlink($adminFile);
    header('Location: /phoenix_admin.php');
    exit;
}

$isLoggedIn = false;
$error = '';
$success = '';

// Check if logged in
if (isset($_SESSION['phoenix_admin']) || file_exists($adminFile)) {
    $isLoggedIn = true;
}

// Handle login
if ($_POST && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Simple hardcoded admin credentials (since database is offline)
    $validCredentials = [
        'vpersonmail@gmail.com' => 'admin123',
        'admin@legozo.com' => 'admin123'
    ];
    
    if (isset($validCredentials[$email]) && $validCredentials[$email] === $password) {
        $_SESSION['phoenix_admin'] = [
            'email' => $email,
            'name' => 'Admin User',
            'login_time' => time()
        ];
        file_put_contents($adminFile, json_encode($_SESSION['phoenix_admin']));
        $isLoggedIn = true;
        $success = 'Login successful!';
    } else {
        $error = 'Invalid credentials';
    }
}

// Database status check (using same method as debug.php)
$dbStatus = 'Unknown';
$dbTables = [];
$dbConnection = null;

$envFile = __DIR__ . '/backend/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    preg_match('/DB_HOST=(.*)/', $env, $host);
    preg_match('/DB_DATABASE=(.*)/', $env, $db);
    preg_match('/DB_USERNAME=(.*)/', $env, $user);
    preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
    
    if (isset($host[1], $db[1], $user[1], $pass[1])) {
        try {
            $dbConnection = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
            $dbStatus = 'Connected';
            $dbTables = $dbConnection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $dbStatus = 'Failed: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phoenix AI - Admin Interface</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .card-header {
            background: #667eea;
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-content {
            padding: 2rem;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .btn:hover { background: #5a67d8; }
        .btn-secondary { background: #718096; }
        .btn-secondary:hover { background: #4a5568; }
        .btn-danger { background: #e53e3e; }
        .btn-danger:hover { background: #c53030; }
        .btn-full { width: 100%; }
        .error {
            background: #fee;
            color: #c53030;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
        }
        .success {
            background: #f0fff4;
            color: #38a169;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c6f6d5;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        .status-item {
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        .status-ok {
            background: #f0fff4;
            border-left-color: #38a169;
        }
        .status-error {
            background: #fed7d7;
            border-left-color: #e53e3e;
        }
        .status-warning {
            background: #fffbeb;
            border-left-color: #d69e2e;
        }
        .actions {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }
        .admin-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$isLoggedIn): ?>
            <!-- Login Form -->
            <div class="card" style="max-width: 400px; margin: 10vh auto;">
                <div class="card-header">
                    <div class="logo">🔥 Phoenix AI Admin</div>
                </div>
                <div class="card-content">
                    <div class="admin-info">
                        <strong>Admin Credentials:</strong><br>
                        Email: <code>vpersonmail@gmail.com</code><br>
                        Password: <code>admin123</code><br><br>
                        Alternative: <code>admin@legozo.com</code> / <code>admin123</code>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? 'vpersonmail@gmail.com') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter admin123" required>
                        </div>
                        
                        <button type="submit" class="btn btn-full">Login to Admin Dashboard</button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="/">← Back to Home</a> |
                        <a href="/debug.php">Debug Info</a>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="card">
                <div class="card-header">
                    <div class="logo">🔥 Phoenix AI Admin Dashboard</div>
                    <div>
                        <span>Welcome, <?= htmlspecialchars($_SESSION['phoenix_admin']['name']) ?></span>
                        <a href="?logout=1" class="btn btn-secondary" style="margin-left: 1rem;">Logout</a>
                    </div>
                </div>
                <div class="card-content">
                    
                    <?php if ($dbStatus !== 'Connected' || count($dbTables) === 0): ?>
                        <div class="alert alert-warning">
                            <strong>⚠️ Database Issue:</strong> 
                            <?php if ($dbStatus !== 'Connected'): ?>
                                Database connection failed: <?= htmlspecialchars($dbStatus) ?>
                            <?php else: ?>
                                Database is empty (0 tables found). Migration may have failed.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <h2>System Status</h2>
                    
                    <div class="status-grid">
                        <div class="status-item status-ok">
                            <strong>✅ PHP:</strong> <?= PHP_VERSION ?>
                        </div>
                        <div class="status-item status-ok">
                            <strong>✅ Admin Access:</strong> Working
                        </div>
                        <div class="status-item <?= $dbStatus === 'Connected' ? 'status-ok' : 'status-error' ?>">
                            <strong><?= $dbStatus === 'Connected' ? '✅' : '❌' ?> Database:</strong> <?= htmlspecialchars($dbStatus) ?>
                        </div>
                        <div class="status-item <?= count($dbTables) > 0 ? 'status-ok' : 'status-error' ?>">
                            <strong><?= count($dbTables) > 0 ? '✅' : '❌' ?> Tables:</strong> <?= count($dbTables) ?> found
                        </div>
                    </div>
                    
                    <?php if (count($dbTables) > 0): ?>
                        <h3 style="margin-top: 2rem;">Database Tables:</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem; margin: 1rem 0;">
                            <?php foreach ($dbTables as $table): ?>
                                <div style="background: #f8fafc; padding: 0.5rem; border-radius: 4px; border: 1px solid #e2e8f0;">
                                    <?= htmlspecialchars($table) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($dbConnection && in_array('users', $dbTables)): ?>
                            <h3>Admin Users:</h3>
                            <?php
                            try {
                                $stmt = $dbConnection->prepare("SELECT id, name, email FROM users WHERE role = 'admin' LIMIT 5");
                                $stmt->execute();
                                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                if (count($admins) > 0) {
                                    echo "<ul style='margin: 1rem 0;'>";
                                    foreach ($admins as $admin) {
                                        echo "<li>ID: {$admin['id']}, Name: " . htmlspecialchars($admin['name']) . ", Email: " . htmlspecialchars($admin['email']) . "</li>";
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "<p>No admin users found in database.</p>";
                                }
                            } catch (Exception $e) {
                                echo "<p style='color: red;'>Error querying users: " . htmlspecialchars($e->getMessage()) . "</p>";
                            }
                            ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <h3 style="margin-top: 2rem;">Admin Tools:</h3>
                    <div class="actions">
                        <a href="/debug.php" class="btn">🔍 Debug Info</a>
                        <a href="/health_check.php" class="btn">🏥 Health Check</a>
                        <a href="/verify_installation.php" class="btn">✅ Verify Installation</a>
                        <?php if ($dbStatus !== 'Connected' || count($dbTables) === 0): ?>
                            <a href="/database_diagnosis.php" class="btn btn-danger">🔧 Database Diagnosis</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($dbStatus !== 'Connected' || count($dbTables) === 0): ?>
                        <div style="margin-top: 2rem; padding: 1.5rem; background: #fee; border-radius: 8px; border: 1px solid #fed7d7;">
                            <h3 style="color: #c53030;">🚨 Critical Issues to Fix:</h3>
                            <ol style="margin-left: 20px; margin-top: 10px; color: #c53030;">
                                <?php if ($dbStatus !== 'Connected'): ?>
                                    <li><strong>Database Connection:</strong> Cannot connect to MySQL database</li>
                                <?php endif; ?>
                                <?php if (count($dbTables) === 0): ?>
                                    <li><strong>Empty Database:</strong> No tables found - migrations may have failed</li>
                                <?php endif; ?>
                                <li><strong>API Endpoints:</strong> Laravel API returning 500 errors</li>
                                <li><strong>Frontend Integration:</strong> React app not built for production</li>
                            </ol>
                            
                            <h4 style="margin-top: 1rem; color: #c53030;">Recommended Actions:</h4>
                            <div class="actions">
                                <a href="/database_diagnosis.php" class="btn btn-danger">🔧 Diagnose Database</a>
                                <a href="/installer/" class="btn btn-secondary">🔄 Re-run Installation</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 2rem; padding: 1.5rem; background: #f0fff4; border-radius: 8px; border: 1px solid #c6f6d5;">
                            <h3 style="color: #38a169;">✅ Database Connected Successfully!</h3>
                            <p style="color: #38a169;">You can now access the full admin features.</p>
                            <div class="actions">
                                <a href="/api/v1/status" class="btn">🧪 Test API</a>
                                <a href="/backend/public/" class="btn">🚀 Laravel Backend</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 8px;">
                        <h3>📋 Access Information:</h3>
                        <p><strong>Admin Interface URL:</strong> https://legozo.com/phoenix_admin.php</p>
                        <p><strong>Domain:</strong> https://legozo.com</p>
                        <p><strong>Environment:</strong> Production (Plesk/Nginx)</p>
                        <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
                        <p><strong>Login Time:</strong> <?= isset($_SESSION['phoenix_admin']) ? date('Y-m-d H:i:s', $_SESSION['phoenix_admin']['login_time']) : 'Not logged in' ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>