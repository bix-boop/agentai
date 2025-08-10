<?php
// Phoenix AI - Standalone Admin Interface
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Authentication logic
$isLoggedIn = isset($_SESSION['phoenix_admin_logged_in']);
$error = '';
$success = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin_interface.php');
    exit;
}

// Handle login
if ($_POST && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $validCredentials = [
        'vpersonmail@gmail.com' => 'admin123',
        'admin@legozo.com' => 'admin123'
    ];
    
    if (isset($validCredentials[$email]) && $validCredentials[$email] === $password) {
        $_SESSION['phoenix_admin_logged_in'] = true;
        $_SESSION['phoenix_admin_email'] = $email;
        $isLoggedIn = true;
        $success = 'Login successful!';
    } else {
        $error = 'Invalid credentials';
    }
}

// Start HTML output
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Phoenix AI - Admin Interface</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }";
echo ".login-container { max-width: 400px; margin: 10vh auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }";
echo "h1 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; text-align: center; }";
echo "h2 { color: #555; margin-top: 30px; margin-bottom: 15px; }";
echo "h3 { color: #666; margin-top: 20px; margin-bottom: 10px; }";
echo "table { border-collapse: collapse; width: 100%; margin: 10px 0; }";
echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
echo "th { background-color: #f2f2f2; }";
echo ".status-ok { color: green; font-weight: bold; }";
echo ".status-error { color: red; font-weight: bold; }";
echo ".status-warning { color: orange; font-weight: bold; }";
echo "pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }";
echo ".form-group { margin-bottom: 20px; }";
echo "label { display: block; margin-bottom: 5px; font-weight: 500; }";
echo "input[type='email'], input[type='password'] { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 1rem; }";
echo ".btn { padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }";
echo ".btn:hover { background: #5a67d8; }";
echo ".btn-secondary { background: #718096; }";
echo ".btn-full { width: 100%; }";
echo ".error { background: #fee; color: #c53030; padding: 10px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fed7d7; }";
echo ".success { background: #f0fff4; color: #38a169; padding: 10px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c6f6d5; }";
echo ".admin-info { background: #f7fafc; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; color: #4a5568; }";
echo "a { color: #007bff; text-decoration: none; }";
echo "a:hover { text-decoration: underline; }";
echo "</style>";
echo "</head>";
echo "<body>";

if (!$isLoggedIn) {
    // Login form
    echo "<div class='login-container'>";
    echo "<h1>🔥 Phoenix AI Admin</h1>";
    echo "<div class='admin-info'><strong>Admin Credentials:</strong><br>Email: <code>vpersonmail@gmail.com</code><br>Password: <code>admin123</code></div>";
    
    if ($error) {
        echo "<div class='error'>" . htmlspecialchars($error) . "</div>";
    }
    if ($success) {
        echo "<div class='success'>" . htmlspecialchars($success) . "</div>";
    }
    
    echo "<form method='POST'>";
    echo "<div class='form-group'><label>Email:</label><input type='email' name='email' value='" . htmlspecialchars($_POST['email'] ?? 'vpersonmail@gmail.com') . "' required></div>";
    echo "<div class='form-group'><label>Password:</label><input type='password' name='password' placeholder='Enter admin123' required></div>";
    echo "<button type='submit' class='btn btn-full'>Login to Admin Dashboard</button>";
    echo "</form>";
    echo "<div style='text-align: center; margin-top: 20px;'><a href='/'>← Back to Home</a> | <a href='/debug.php'>Debug Info</a></div>";
    echo "</div>";
} else {
    // Admin dashboard
    echo "<div class='container'>";
    echo "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>";
    echo "<h1>🔥 Phoenix AI - Admin Dashboard</h1>";
    echo "<div><span>Welcome, " . htmlspecialchars($_SESSION['phoenix_admin_email']) . "</span> <a href='?logout=1' class='btn btn-secondary'>Logout</a></div>";
    echo "</div>";

    if ($success) {
        echo "<div class='success'>" . htmlspecialchars($success) . "</div>";
    }

    echo "<h2>📋 System Status</h2>";
    
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
    
    // Display system status
    echo "<table>";
    echo "<tr><td><strong>PHP Version</strong></td><td class='status-ok'>✅ " . PHP_VERSION . "</td></tr>";
    echo "<tr><td><strong>Laravel Framework</strong></td><td class='status-warning'>⚠️ Bootstrap Issues</td></tr>";
    echo "<tr><td><strong>Database Connection</strong></td><td class='" . ($dbStatus === 'Connected' ? 'status-ok' : 'status-error') . "'>" . ($dbStatus === 'Connected' ? '✅' : '❌') . " " . htmlspecialchars($dbStatus) . "</td></tr>";
    echo "<tr><td><strong>Database Tables</strong></td><td class='" . (count($dbTables) > 0 ? 'status-ok' : 'status-error') . "'>" . (count($dbTables) > 0 ? '✅' : '❌') . " " . count($dbTables) . " tables found</td></tr>";
    echo "<tr><td><strong>Admin Interface</strong></td><td class='status-ok'>✅ Working (Standalone Mode)</td></tr>";
    echo "</table>";
    
    if (count($dbTables) > 0) {
        echo "<h3>Database Tables:</h3>";
        echo "<ul>";
        foreach ($dbTables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
        
        // Check for admin users if users table exists
        if ($dbConnection && in_array('users', $dbTables)) {
            echo "<h3>Admin Users:</h3>";
            try {
                $stmt = $dbConnection->prepare("SELECT id, name, email FROM users WHERE role = 'admin' LIMIT 5");
                $stmt->execute();
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($admins) > 0) {
                    echo "<ul>";
                    foreach ($admins as $admin) {
                        echo "<li>ID: {$admin['id']}, Name: " . htmlspecialchars($admin['name']) . ", Email: " . htmlspecialchars($admin['email']) . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='status-warning'>⚠️ No admin users found in database.</p>";
                }
            } catch (Exception $e) {
                echo "<p class='status-error'>❌ Error querying users: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<div class='error'>";
        echo "<h3>🚨 Critical Issue: Database is Empty</h3>";
        echo "<p>The database connection works, but no tables were found. This means:</p>";
        echo "<ul>";
        echo "<li>The migration process failed during installation</li>";
        echo "<li>Tables were created in a different database</li>";
        echo "<li>Database was reset after installation</li>";
        echo "</ul>";
        echo "<p><strong>Recommended Actions:</strong></p>";
        echo "<a href='/installer/' class='btn'>🔄 Re-run Installation</a>";
        echo "<a href='/run_migrations.php' class='btn'>🗄️ Run Migrations</a>";
        echo "</div>";
    }
    
    echo "<h2>🚀 Admin Tools</h2>";
    echo "<div>";
    echo "<a href='/debug.php' class='btn'>🔍 Debug Info</a>";
    echo "<a href='/health_check.php' class='btn'>🏥 Health Check</a>";
    echo "<a href='/verify_installation.php' class='btn'>✅ Verify Installation</a>";
    echo "<a href='/database_diagnosis.php' class='btn'>🔧 Database Diagnosis</a>";
    if (count($dbTables) === 0) {
        echo "<a href='/run_migrations.php' class='btn'>🗄️ Run Migrations</a>";
    }
    echo "</div>";
    
    echo "<h2>📋 Current Configuration</h2>";
    echo "<table>";
    echo "<tr><td><strong>Domain</strong></td><td>https://legozo.com</td></tr>";
    echo "<tr><td><strong>Admin Email</strong></td><td>" . htmlspecialchars($_SESSION['phoenix_admin_email']) . "</td></tr>";
    echo "<tr><td><strong>Environment</strong></td><td>Production (Plesk/Nginx)</td></tr>";
    echo "<tr><td><strong>Admin Interface URL</strong></td><td>https://legozo.com/admin_interface.php</td></tr>";
    echo "</table>";
    
    echo "</div>";
}

echo "</body>";
echo "</html>";