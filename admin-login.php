<?php
// Simple Admin Login Interface for Phoenix AI
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_token'])) {
    header('Location: /admin-dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_POST && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Make API request to Laravel backend
    $postData = json_encode([
        'email' => $email,
        'password' => $password
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            'content' => $postData
        ]
    ]);
    
    $apiUrl = 'https://legozo.com/api/v1/auth/login';
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            // Check if user is admin
            if (isset($data['user']['role']) && $data['user']['role'] === 'admin') {
                $_SESSION['admin_token'] = $data['token'];
                $_SESSION['admin_user'] = $data['user'];
                header('Location: /admin-dashboard.php');
                exit;
            } else {
                $error = 'Admin access required';
            }
        } else {
            $error = $data['message'] ?? 'Login failed';
        }
    } else {
        $error = 'Unable to connect to authentication service';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phoenix AI - Admin Login</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 0.9rem;
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
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #5a67d8;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🔥 Phoenix AI</h1>
            <p>Admin Dashboard Login</p>
        </div>
        
        <div class="admin-info">
            <strong>Admin Credentials:</strong><br>
            Email: <code>vpersonmail@gmail.com</code><br>
            Password: <code>admin123</code> (default)
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
                <input type="password" id="password" name="password" placeholder="Enter your admin password" required>
            </div>
            
            <button type="submit" class="btn">Login to Admin Dashboard</button>
        </form>
        
        <div class="links">
            <a href="/">← Back to Home</a>
            <a href="/debug.php">Debug Info</a>
            <a href="/health_check.php">Health Check</a>
        </div>
    </div>
</body>
</html>