<?php
// Phoenix AI - Reset Admin Password
echo "<h1>🔑 Phoenix AI - Reset Admin Password</h1>";

$envFile = __DIR__ . '/backend/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    preg_match('/DB_HOST=(.*)/', $env, $host);
    preg_match('/DB_DATABASE=(.*)/', $env, $db);
    preg_match('/DB_USERNAME=(.*)/', $env, $user);
    preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
    
    if (isset($host[1], $db[1], $user[1], $pass[1])) {
        try {
            $pdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1]);
            echo "<p>✅ Database connection: Connected</p>";
            
            // Check current admin users
            $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'admin'");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h2>Current Admin Users:</h2>";
            foreach ($admins as $admin) {
                echo "<p>ID: {$admin['id']}, Name: {$admin['name']}, Email: {$admin['email']}</p>";
            }
            
            if (isset($_GET['reset'])) {
                echo "<h2>🔧 Resetting Admin Password...</h2>";
                
                // Reset password for vpersonmail@gmail.com
                $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'vpersonmail@gmail.com'");
                $result = $stmt->execute([$newPassword]);
                
                if ($result) {
                    echo "<p style='color:green;font-weight:bold;'>✅ Password reset successful!</p>";
                    echo "<p><strong>Email:</strong> vpersonmail@gmail.com</p>";
                    echo "<p><strong>New Password:</strong> admin123</p>";
                    
                    // Test the new password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE email = 'vpersonmail@gmail.com'");
                    $stmt->execute();
                    $hashedPassword = $stmt->fetchColumn();
                    
                    if (password_verify('admin123', $hashedPassword)) {
                        echo "<p style='color:green;'>✅ Password verification: SUCCESS</p>";
                        echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
                        echo "<h3>🎉 Admin Login Ready!</h3>";
                        echo "<p>You can now login with:</p>";
                        echo "<p><strong>Email:</strong> vpersonmail@gmail.com</p>";
                        echo "<p><strong>Password:</strong> admin123</p>";
                        echo "<p><a href='/api/v1/auth/login' target='_blank'>Test API Login</a></p>";
                        echo "</div>";
                    } else {
                        echo "<p style='color:red;'>❌ Password verification failed</p>";
                    }
                } else {
                    echo "<p style='color:red;'>❌ Password reset failed</p>";
                }
            } else {
                echo "<p><a href='?reset=1' style='background:#dc3545;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔑 Reset Admin Password to admin123</a></p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color:red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Could not read database configuration</p>";
    }
} else {
    echo "<p style='color:red;'>❌ .env file not found</p>";
}

echo "<p style='margin-top:20px;'>";
echo "<a href='/debug.php' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔍 Debug Info</a> ";
echo "<a href='/health_check.php' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;margin-left:10px;'>🏥 Health Check</a>";
echo "</p>";
?>