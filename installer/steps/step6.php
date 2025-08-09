<?php
// Step 6: Installation Complete

if (!isset($_SESSION['installation_complete']) || !$_SESSION['installation_complete']) {
    header('Location: ?step=1');
    exit;
}

$config = $_SESSION['installer_config'] ?? [];
$siteUrl = $config['site_url'] ?? '';
$adminEmail = $config['admin_email'] ?? '';

// Clear session data
unset($_SESSION['installer_config']);
unset($_SESSION['installation_complete']);
?>

<div class="step-content">
    <div class="installation-complete fade-in-up">
        <h2>🎉 Installation Complete!</h2>
        <p>Congratulations! Phoenix AI has been successfully installed and configured on your server.</p>
        
        <div class="next-steps">
            <h3>🚀 What's Next?</h3>
            <div class="two-column">
                <div>
                    <h4>Immediate Steps:</h4>
                    <ul>
                        <li>Delete the installer directory for security</li>
                        <li>Log into your admin dashboard</li>
                        <li>Configure payment gateways</li>
                        <li>Set up email settings</li>
                    </ul>
                </div>
                <div>
                    <h4>Platform Setup:</h4>
                    <ul>
                        <li>Create additional AI assistants</li>
                        <li>Customize credit packages</li>
                        <li>Configure site appearance</li>
                        <li>Test AI functionality</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="installation-summary">
            <h3>📊 Installation Summary</h3>
            <div class="two-column">
                <div>
                    <strong>What was installed:</strong>
                    <ul>
                        <li>✅ Database tables created</li>
                        <li>✅ Admin user account</li>
                        <li>✅ 6 default categories</li>
                        <li>✅ 3 sample AI assistants</li>
                        <li>✅ 4 credit packages</li>
                    </ul>
                </div>
                <div>
                    <strong>Configuration:</strong>
                    <ul>
                        <li>🌐 Site URL: <?= htmlspecialchars($siteUrl) ?></li>
                        <li>👤 Admin: <?= htmlspecialchars($adminEmail) ?></li>
                        <li>🤖 OpenAI API configured</li>
                        <li>💳 Ready for payments</li>
                        <li>🔒 Security enabled</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <strong>🔒 Important Security Steps:</strong>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <li><strong>Delete the installer directory</strong> - Run: <code>rm -rf installer/</code></li>
                <li>Change default file permissions if needed</li>
                <li>Set up SSL certificate for HTTPS</li>
                <li>Configure regular backups</li>
                <li>Monitor your OpenAI usage</li>
            </ul>
        </div>

        <?php if (empty($config['openai_api_key'])): ?>
        <div class="alert alert-warning">
            <strong>🤖 OpenAI API Key Required for AI Features:</strong>
            <p>You can add your OpenAI API key later to enable AI functionality:</p>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <li>Go to Admin Dashboard → Settings</li>
                <li>Add your OpenAI API key (supports both sk-... and sk-proj-... formats)</li>
                <li>Test the connection to ensure it works</li>
                <li>AI features will be enabled immediately</li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>📚 Resources & Support:</strong>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <li>Admin Dashboard: <a href="<?= $siteUrl ?>/admin" target="_blank"><?= $siteUrl ?>/admin</a></li>
                <li>User Registration: <a href="<?= $siteUrl ?>/register" target="_blank"><?= $siteUrl ?>/register</a></li>
                <li>API Documentation: <a href="<?= $siteUrl ?>/api/docs" target="_blank"><?= $siteUrl ?>/api/docs</a></li>
                <li>Landing Page: <a href="<?= $siteUrl ?>" target="_blank"><?= $siteUrl ?></a></li>
            </ul>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <a href="<?= $siteUrl ?>/admin" class="btn btn-primary btn-large" target="_blank">
                🎛️ Open Admin Dashboard
            </a>
            <a href="<?= $siteUrl ?>" class="btn btn-success btn-large" target="_blank" style="margin-left: 15px;">
                🌟 View Your Site
            </a>
        </div>

        <div class="welcome-features" style="margin-top: 40px;">
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h4>Monetization Ready</h4>
                <p>Credit packages are configured and ready for sales. Add your Stripe/PayPal keys to start earning.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h4>AI Assistants</h4>
                <p>3 sample AI assistants are ready to use. Create more specialized assistants for your users.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h4>Analytics Dashboard</h4>
                <p>Comprehensive admin dashboard with user analytics, revenue tracking, and system monitoring.</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h3>🎊 Thank You for Choosing Phoenix AI!</h3>
            <p>You now have a complete, production-ready AI assistant platform.<br>
            Start building your AI business today!</p>
        </div>
    </div>
</div>

<script>
// Disable browser back button to prevent returning to installer
history.pushState(null, null, location.href);
window.onpopstate = function() {
    history.go(1);
};

// Auto-cleanup reminder
setTimeout(function() {
    if (confirm('Installation is complete! Would you like to be reminded to delete the installer directory for security?')) {
        alert('Remember to run: rm -rf installer/ \n\nThis will remove the installation files and secure your site.');
    }
}, 3000);
</script>