<?php
// Step 3: Database Configuration

$error = null;
$success = null;

// Handle database connection test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'test') {
    try {
        $host = $_POST['db_host'] ?? '';
        $port = $_POST['db_port'] ?? '';
        $database = $_POST['db_name'] ?? '';
        $username = $_POST['db_username'] ?? '';
        $password = $_POST['db_password'] ?? '';
        
        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Database connection successful']);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    try {
        $dbConfig = [
            'host' => $_POST['db_host'] ?? '',
            'port' => $_POST['db_port'] ?? '',
            'database' => $_POST['db_name'] ?? '',
            'username' => $_POST['db_username'] ?? '',
            'password' => $_POST['db_password'] ?? '',
        ];
        
        // Validate required fields
        foreach ($dbConfig as $key => $value) {
            if (empty($value) && $key !== 'password') {
                throw new Exception("Database {$key} is required");
            }
        }
        
        // Test database connection
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Store database config in session
        $_SESSION['installer_config']['db_host'] = $dbConfig['host'];
        $_SESSION['installer_config']['db_port'] = $dbConfig['port'];
        $_SESSION['installer_config']['db_name'] = $dbConfig['database'];
        $_SESSION['installer_config']['db_username'] = $dbConfig['username'];
        $_SESSION['installer_config']['db_password'] = $dbConfig['password'];
        
        $success = "Database connection verified successfully!";
        
        // Redirect to next step
        header('Location: ?step=4');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get saved values from session
$saved = $_SESSION['installer_config'] ?? [];
?>

<div class="step-content">
    <h2>Database Configuration</h2>
    <p>Enter your MySQL database connection details. Phoenix AI will create all necessary tables automatically.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>Database Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Success:</strong> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" id="database-form" class="installer-form">
        <div class="two-column">
            <div class="form-group">
                <label for="db_host">Database Host</label>
                <input 
                    type="text" 
                    id="db_host" 
                    name="db_host" 
                    value="<?= htmlspecialchars($saved['db_host'] ?? 'localhost') ?>" 
                    required
                    data-tooltip="Usually 'localhost' for local servers"
                >
                <small>Usually 'localhost' for most hosting providers</small>
            </div>

            <div class="form-group">
                <label for="db_port">Database Port</label>
                <input 
                    type="number" 
                    id="db_port" 
                    name="db_port" 
                    value="<?= htmlspecialchars($saved['db_port'] ?? '3306') ?>" 
                    required
                    data-tooltip="Default MySQL port is 3306"
                >
                <small>Default MySQL port is 3306</small>
            </div>
        </div>

        <div class="form-group">
            <label for="db_name">Database Name</label>
            <input 
                type="text" 
                id="db_name" 
                name="db_name" 
                value="<?= htmlspecialchars($saved['db_name'] ?? '') ?>" 
                required
                data-tooltip="The name of your MySQL database"
            >
            <small>The database must already exist. Phoenix AI will create the tables.</small>
        </div>

        <div class="two-column">
            <div class="form-group">
                <label for="db_username">Database Username</label>
                <input 
                    type="text" 
                    id="db_username" 
                    name="db_username" 
                    value="<?= htmlspecialchars($saved['db_username'] ?? '') ?>" 
                    required
                    data-tooltip="MySQL username with full access to the database"
                >
                <small>User must have CREATE, ALTER, INSERT, UPDATE, DELETE privileges</small>
            </div>

            <div class="form-group">
                <label for="db_password">Database Password</label>
                <input 
                    type="password" 
                    id="db_password" 
                    name="db_password" 
                    value="<?= htmlspecialchars($saved['db_password'] ?? '') ?>"
                    data-tooltip="Password for the database user"
                >
                <small>Leave blank if no password is required</small>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Database Setup Tips:</strong>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <li>Create a new database specifically for Phoenix AI</li>
                <li>Use a dedicated database user with limited privileges</li>
                <li>Make sure the database uses UTF8 character set</li>
                <li>Test the connection before proceeding</li>
            </ul>
        </div>

        <div id="db-test-result"></div>

        <div class="form-actions">
            <a href="?step=2" class="btn btn-secondary">← Back</a>
            <div>
                <button type="button" onclick="testDatabaseConnection()" class="btn btn-secondary">
                    🔍 Test Connection
                </button>
                <button type="submit" class="btn btn-primary">
                    Continue to Configuration →
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function testDatabaseConnection() {
    const form = document.querySelector('#database-form');
    const formData = new FormData(form);
    const testBtn = event.target;
    
    testBtn.disabled = true;
    testBtn.innerHTML = '<span class="loading"></span> Testing...';
    
    fetch('?step=3&action=test', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('db-test-result');
        
        if (data.success) {
            resultDiv.className = 'database-test success fade-in-up';
            resultDiv.innerHTML = `
                <h4>✅ Database Connection Successful</h4>
                <p>Successfully connected to the database server.</p>
            `;
        } else {
            resultDiv.className = 'database-test error fade-in-up';
            resultDiv.innerHTML = `
                <h4>❌ Database Connection Failed</h4>
                <p>${data.error || 'Could not connect to the database'}</p>
            `;
        }
    })
    .catch(error => {
        const resultDiv = document.getElementById('db-test-result');
        resultDiv.className = 'database-test error fade-in-up';
        resultDiv.innerHTML = `
            <h4>❌ Connection Test Failed</h4>
            <p>Network error: ${error.message}</p>
        `;
    })
    .finally(() => {
        testBtn.disabled = false;
        testBtn.textContent = '🔍 Test Connection';
    });
}
</script>