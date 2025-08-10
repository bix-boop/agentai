<?php
// Simple File-Based Admin Dashboard for Phoenix AI
session_start();

// Check if logged in
if (!isset($_SESSION['admin_user'])) {
    header('Location: /admin-login-simple.php');
    exit;
}

$admin = $_SESSION['admin_user'];

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin-login-simple.php');
    exit;
}

// Simple stats (mock data since database isn't working)
$stats = [
    'total_users' => 'N/A (DB offline)',
    'total_chats' => 'N/A (DB offline)', 
    'total_messages' => 'N/A (DB offline)',
    'total_revenue' => 'N/A (DB offline)'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phoenix AI - Admin Dashboard (Simple Mode)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #f8fafc;
            color: #2d3748;
        }
        
        .header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
        
        .section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .section-header h2 {
            color: #2d3748;
            font-size: 1.25rem;
        }
        
        .section-content {
            padding: 1.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            display: inline-block;
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
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .alert-info {
            background: #ebf8ff;
            color: #2c5282;
            border: 1px solid #bee3f8;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .status-item {
            padding: 1rem;
            border-radius: 4px;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🔥 Phoenix AI Admin (Simple Mode)</div>
        <div class="user-menu">
            <span>Welcome, <?= htmlspecialchars($admin['name']) ?></span>
            <a href="?logout=1" class="btn btn-secondary">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="alert alert-warning">
            <strong>⚠️ Database Connection Issue:</strong> The admin dashboard is running in simplified mode due to database connectivity issues. 
            <a href="/fix_database.php">Click here to fix the database connection</a> to access full functionality.
        </div>
        
        <h1>Admin Dashboard</h1>
        
        <div class="quick-actions">
            <a href="/fix_database.php" class="btn btn-danger">🔧 Fix Database Connection</a>
            <a href="/debug.php" class="btn btn-secondary">🔍 Debug Info</a>
            <a href="/health_check.php" class="btn btn-secondary">🏥 Health Check</a>
            <a href="/verify_installation.php" class="btn btn-secondary">✅ Verify Installation</a>
            <a href="/test_complete_platform.php" class="btn">🧪 Test Platform</a>
        </div>
        
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?= $stats['total_users'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Chats</h3>
                <div class="value"><?= $stats['total_chats'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Messages</h3>
                <div class="value"><?= $stats['total_messages'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Revenue</h3>
                <div class="value"><?= $stats['total_revenue'] ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>🔧 System Status</h2>
            </div>
            <div class="section-content">
                <div class="status-grid">
                    <div class="status-item status-ok">
                        <strong>✅ Installation:</strong> Complete
                    </div>
                    <div class="status-item status-ok">
                        <strong>✅ PHP:</strong> 8.3.24
                    </div>
                    <div class="status-item status-ok">
                        <strong>✅ Laravel:</strong> 12.22.1
                    </div>
                    <div class="status-item status-error">
                        <strong>❌ Database:</strong> Connection Issues
                    </div>
                    <div class="status-item status-error">
                        <strong>❌ API:</strong> Not Responding
                    </div>
                    <div class="status-item status-warning">
                        <strong>⚠️ Frontend:</strong> Static Mode
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>🚀 Next Steps to Fix Platform</h2>
            </div>
            <div class="section-content">
                <div class="alert alert-info">
                    <strong>Priority Actions:</strong>
                    <ol style="margin-left: 20px; margin-top: 10px;">
                        <li><strong>Fix Database Connection:</strong> <a href="/fix_database.php">Run database fix script</a></li>
                        <li><strong>Test API Endpoints:</strong> Once database is fixed, test <code>/api/v1/status</code></li>
                        <li><strong>Build Frontend:</strong> Build React app for full admin interface</li>
                        <li><strong>Configure Production:</strong> Set up proper production environment</li>
                    </ol>
                </div>
                
                <h3>Available Tools:</h3>
                <div class="quick-actions">
                    <a href="/fix_database.php" class="btn">🔧 Database Fix</a>
                    <a href="/test_complete_platform.php" class="btn">🧪 Platform Test</a>
                    <a href="/final_optimization.php" class="btn">⚡ Optimization</a>
                    <a href="/installer/" class="btn btn-secondary">🔄 Re-install</a>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>📋 Current Configuration</h2>
            </div>
            <div class="section-content">
                <p><strong>Domain:</strong> https://legozo.com</p>
                <p><strong>Backend Path:</strong> /var/www/vhosts/legozo.com/httpdocs/backend</p>
                <p><strong>Frontend Path:</strong> /var/www/vhosts/legozo.com/httpdocs/frontend</p>
                <p><strong>Admin Email:</strong> vpersonmail@gmail.com</p>
                <p><strong>Environment:</strong> Production (Plesk)</p>
            </div>
        </div>
    </div>
</body>
</html>