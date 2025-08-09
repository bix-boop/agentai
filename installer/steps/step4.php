<?php
// Step 4: Application Configuration

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $config = [
            'site_name' => $_POST['site_name'] ?? '',
            'site_url' => $_POST['site_url'] ?? '',
            'admin_name' => $_POST['admin_name'] ?? '',
            'admin_email' => $_POST['admin_email'] ?? '',
            'admin_password' => $_POST['admin_password'] ?? '',
            'openai_api_key' => $_POST['openai_api_key'] ?? '',
        ];
        
        // Validate required fields
        $required = ['site_name', 'site_url', 'admin_name', 'admin_email', 'admin_password', 'openai_api_key'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required");
            }
        }
        
        // Validate email
        if (!filter_var($config['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address");
        }
        
        // Validate password
        if (strlen($config['admin_password']) < 8) {
            throw new Exception("Admin password must be at least 8 characters long");
        }
        
        // Validate OpenAI API key format
        if (!preg_match('/^sk-[a-zA-Z0-9]{48}$/', $config['openai_api_key'])) {
            throw new Exception("Invalid OpenAI API key format. Should start with 'sk-' followed by 48 characters");
        }
        
        // Store config in session
        foreach ($config as $key => $value) {
            $_SESSION['installer_config'][$key] = $value;
        }
        
        $success = "Configuration saved successfully!";
        
        // Redirect to installation step
        header('Location: ?step=5');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get saved values from session
$saved = $_SESSION['installer_config'] ?? [];

// Try to detect site URL
$detectedUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . 
               rtrim(dirname(dirname($_SERVER['REQUEST_URI'])), '/');
?>

<div class="step-content">
    <h2>Application Configuration</h2>
    <p>Configure your Phoenix AI platform settings and create the administrator account.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>Configuration Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Success:</strong> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="installer-form">
        <h3>🌐 Site Configuration</h3>
        
        <div class="form-group">
            <label for="site_name">Site Name</label>
            <input 
                type="text" 
                id="site_name" 
                name="site_name" 
                value="<?= htmlspecialchars($saved['site_name'] ?? 'Phoenix AI') ?>" 
                required
                data-tooltip="The name of your AI platform"
            >
            <small>This will appear in the browser title and throughout the platform</small>
        </div>

        <div class="form-group">
            <label for="site_url">Site URL</label>
            <input 
                type="url" 
                id="site_url" 
                name="site_url" 
                value="<?= htmlspecialchars($saved['site_url'] ?? $detectedUrl) ?>" 
                required
                data-tooltip="The full URL where your site will be accessible"
            >
            <small>Include http:// or https:// - this should be your domain without trailing slash</small>
        </div>

        <h3>👤 Administrator Account</h3>
        
        <div class="two-column">
            <div class="form-group">
                <label for="admin_name">Admin Name</label>
                <input 
                    type="text" 
                    id="admin_name" 
                    name="admin_name" 
                    value="<?= htmlspecialchars($saved['admin_name'] ?? '') ?>" 
                    required
                    data-tooltip="Full name of the administrator"
                >
                <small>This will be displayed as the admin user's name</small>
            </div>

            <div class="form-group">
                <label for="admin_email">Admin Email</label>
                <input 
                    type="email" 
                    id="admin_email" 
                    name="admin_email" 
                    value="<?= htmlspecialchars($saved['admin_email'] ?? '') ?>" 
                    required
                    data-tooltip="Email address for the admin account"
                >
                <small>Use this email to log into the admin panel</small>
            </div>
        </div>

        <div class="form-group">
            <label for="admin_password">Admin Password</label>
            <input 
                type="password" 
                id="admin_password" 
                name="admin_password" 
                value="<?= htmlspecialchars($saved['admin_password'] ?? '') ?>" 
                required
                minlength="8"
                data-tooltip="Strong password for the admin account"
            >
            <small>Minimum 8 characters - use a strong, unique password</small>
        </div>

        <h3>🤖 AI Configuration</h3>
        
        <div class="form-group">
            <label for="openai_api_key">OpenAI API Key</label>
            <input 
                type="password" 
                id="openai_api_key" 
                name="openai_api_key" 
                value="<?= htmlspecialchars($saved['openai_api_key'] ?? '') ?>" 
                required
                pattern="sk-[a-zA-Z0-9]{48}"
                data-tooltip="Your OpenAI API key starting with 'sk-'"
            >
            <small>Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></small>
        </div>

        <div class="alert alert-info">
            <strong>OpenAI API Key Requirements:</strong>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <li>Must have access to GPT-3.5-turbo or GPT-4</li>
                <li>Should have DALL-E access for image generation</li>
                <li>Make sure you have sufficient credit balance</li>
                <li>API key should start with 'sk-' followed by 48 characters</li>
            </ul>
        </div>

        <div class="alert alert-warning">
            <strong>Security Notice:</strong>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <li>Your API key will be securely stored in environment variables</li>
                <li>Never share your OpenAI API key publicly</li>
                <li>Monitor your OpenAI usage to avoid unexpected charges</li>
                <li>Set usage limits in your OpenAI dashboard</li>
            </ul>
        </div>

        <div class="form-actions">
            <a href="?step=3" class="btn btn-secondary">← Back</a>
            <button type="submit" class="btn btn-primary">
                Continue to Installation →
            </button>
        </div>
    </form>
</div>