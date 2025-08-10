<?php
// Phoenix AI Admin Interface - Accessible via /backend/public/admin.php
session_start();

// Simple file-based user storage (since database has issues)
$usersFile = dirname(__DIR__, 2) . '/admin_users.json';

// Initialize users file if it doesn't exist
if (!file_exists($usersFile)) {
    $defaultUsers = [
        [
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'vpersonmail@gmail.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin'
        ],
        [
            'id' => 2,
            'name' => 'Admin',
            'email' => 'admin@legozo.com', 
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin'
        ]
    ];
    file_put_contents($usersFile, json_encode($defaultUsers, JSON_PRETTY_PRINT));
}

$error = '';
$success = '';
$showDashboard = false;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    $success = 'Logged out successfully';
}

// Check if already logged in
if (isset($_SESSION['admin_user']) && !isset($_GET['logout'])) {
    $showDashboard = true;
    $admin = $_SESSION['admin_user'];
}

// Handle login form submission
if ($_POST && isset($_POST['email']) && isset($_POST['password']) && !$showDashboard) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $users = json_decode(file_get_contents($usersFile), true);
    
    foreach ($users as $user) {
        if ($user['email'] === $email && password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                $_SESSION['admin_user'] = $user;
                $_SESSION['admin_token'] = 'simple_' . bin2hex(random_bytes(32));
                $showDashboard = true;
                $admin = $user;
                $success = 'Login successful!';
                break;
            } else {
                $error = 'Admin access required';
                break;
            }
        }
    }
    
    if (!$showDashboard && !$error) {
        $error = 'Invalid email or password';
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin: 10vh auto;
        }
        
        .dashboard-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 2rem auto;
        }
        
        .header {
            background: #667eea;
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .content {
            padding: 2rem;
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
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
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
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #718096;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        .btn-danger {
            background: #e53e3e;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .btn-full {
            width: 100%;
        }
        
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
        
        .admin-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #4a5568;
        }
        
        .admin-info strong {
            color: #2d3748;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-size: 0.9rem;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .stat-card h3 {
            color: #4a5568;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #2d3748;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>

<?php if (!$showDashboard): ?>
    <!-- Login Form -->
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #667eea; font-size: 2rem; margin-bottom: 5px;">🔥 Phoenix AI</h1>
            <p style="color: #666; font-size: 0.9rem;">Admin Interface</p>
        </div>
        
        <div class="admin-info">
            <strong>Default Admin Credentials:</strong><br>
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
        
        <div class="links">
            <a href="/">← Back to Home</a>
            <a href="/debug.php">Debug Info</a>
            <a href="/health_check.php">Health Check</a>
        </div>
    </div>

<?php else: ?>
    <!-- Admin Dashboard -->
    <div class="dashboard-container">
        <div class="header">
            <div class="logo">🔥 Phoenix AI Admin</div>
            <div>
                <span>Welcome, <?= htmlspecialchars($admin['name']) ?></span>
                <a href="?logout=1" class="btn btn-secondary" style="margin-left: 1rem;">Logout</a>
            </div>
        </div>
        
        <div class="content">
            <div class="alert alert-warning">
                <strong>⚠️ Database Issue:</strong> Running in simplified mode due to database connectivity problems. 
                Some features may be limited until the database connection is fixed.
            </div>
            
            <h2>Admin Dashboard</h2>
            
            <div class="actions">
                <a href="/debug.php" class="btn">🔍 Debug Info</a>
                <a href="/health_check.php" class="btn">🏥 Health Check</a>
                <a href="/verify_installation.php" class="btn">✅ Verify Installation</a>
                <a href="/fix_database_plesk.php" class="btn btn-danger">🔧 Fix Database</a>
            </div>
            
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>System Status</h3>
                    <div class="value">⚠️ Limited</div>
                </div>
                <div class="stat-card">
                    <h3>Database</h3>
                    <div class="value">❌ Offline</div>
                </div>
                <div class="stat-card">
                    <h3>API Status</h3>
                    <div class="value">❌ Error</div>
                </div>
                <div class="stat-card">
                    <h3>Admin Access</h3>
                    <div class="value">✅ Working</div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 8px;">
                <h3>🚀 Next Steps:</h3>
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li><strong>Fix Database:</strong> <a href="/fix_database_plesk.php">Run database fix script</a></li>
                    <li><strong>Test API:</strong> Once database is fixed, test <code>/api/v1/status</code></li>
                    <li><strong>Access Full Features:</strong> Database-dependent features will be available</li>
                </ol>
                
                <div style="margin-top: 1rem;">
                    <a href="/fix_database_plesk.php" class="btn btn-danger">🔧 Fix Database Now</a>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding: 1.5rem; background: #ebf8ff; border-radius: 8px; border: 1px solid #bee3f8;">
                <h3>📋 Current Configuration:</h3>
                <p><strong>Domain:</strong> https://legozo.com</p>
                <p><strong>Admin Email:</strong> <?= htmlspecialchars($admin['email']) ?></p>
                <p><strong>Environment:</strong> Production (Plesk/Nginx)</p>
                <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
                <p><strong>Access Method:</strong> /backend/public/admin.php</p>
            </div>
        </div>
    </div>
<?php endif; ?>

</body>
</html>