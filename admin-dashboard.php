<?php
// Simple Admin Dashboard for Phoenix AI
session_start();

// Check if logged in
if (!isset($_SESSION['admin_token'])) {
    header('Location: /admin-login.php');
    exit;
}

$token = $_SESSION['admin_token'];
$admin = $_SESSION['admin_user'];

// Function to make API requests
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $token;
    
    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json'
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $data ? json_encode($data) : null
        ]
    ]);
    
    $url = 'https://legozo.com/api/v1' . $endpoint;
    $response = @file_get_contents($url, false, $context);
    
    return $response ? json_decode($response, true) : null;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin-login.php');
    exit;
}

// Get dashboard data
$stats = makeApiRequest('/admin/analytics/dashboard');
$recentUsers = makeApiRequest('/admin/users?limit=5');
$recentChats = makeApiRequest('/admin/chats?limit=5');
$pendingTransactions = makeApiRequest('/admin/transactions?status=pending');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phoenix AI - Admin Dashboard</title>
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
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-pending {
            background: #fed7d7;
            color: #c53030;
        }
        
        .badge-completed {
            background: #c6f6d5;
            color: #38a169;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🔥 Phoenix AI Admin</div>
        <div class="user-menu">
            <span>Welcome, <?= htmlspecialchars($admin['name']) ?></span>
            <a href="?logout=1" class="btn btn-secondary">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="quick-actions">
            <a href="/admin-users.php" class="btn">Manage Users</a>
            <a href="/admin-transactions.php" class="btn">Transactions</a>
            <a href="/admin-ai-assistants.php" class="btn">AI Assistants</a>
            <a href="/debug.php" class="btn btn-secondary">Debug</a>
        </div>
        
        <?php if ($stats && isset($stats['success']) && $stats['success']): ?>
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="value"><?= $stats['data']['total_users'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Chats</h3>
                    <div class="value"><?= $stats['data']['total_chats'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Messages</h3>
                    <div class="value"><?= $stats['data']['total_messages'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <h3>Revenue</h3>
                    <div class="value">$<?= number_format(($stats['data']['total_revenue'] ?? 0) / 100, 2) ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="error">
                Unable to load dashboard statistics. API connection issue.
            </div>
        <?php endif; ?>
        
        <div class="section">
            <div class="section-header">
                <h2>Pending Transactions</h2>
            </div>
            <div class="section-content">
                <?php if ($pendingTransactions && isset($pendingTransactions['data']) && count($pendingTransactions['data']) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingTransactions['data'] as $transaction): ?>
                                <tr>
                                    <td><?= $transaction['id'] ?></td>
                                    <td><?= htmlspecialchars($transaction['user']['name'] ?? 'Unknown') ?></td>
                                    <td>$<?= number_format($transaction['price_cents'] / 100, 2) ?></td>
                                    <td><?= ucfirst($transaction['payment_method']) ?></td>
                                    <td><?= date('M j, Y', strtotime($transaction['created_at'])) ?></td>
                                    <td>
                                        <button onclick="approveTransaction(<?= $transaction['id'] ?>)" class="btn">Approve</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No pending transactions</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Recent Users</h2>
            </div>
            <div class="section-content">
                <?php if ($recentUsers && isset($recentUsers['data'])): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Credits</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers['data'] as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= number_format($user['credits_balance']) ?></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Unable to load user data</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function approveTransaction(id) {
            if (confirm('Are you sure you want to approve this transaction?')) {
                fetch('/api/v1/admin/transactions/' + id + '/approve', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer <?= $token ?>',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        verification_notes: 'Approved via admin dashboard'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Transaction approved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error approving transaction: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>