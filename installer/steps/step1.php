<?php
// Step 1: Welcome
?>
<div class="step-content welcome-content">
    <h2>Welcome to Phoenix AI</h2>
    <p>Thank you for choosing Phoenix AI! This installation wizard will guide you through setting up your AI assistant platform in just a few simple steps.</p>
    
    <div class="welcome-features">
        <div class="feature-card">
            <div class="feature-icon">🤖</div>
            <h4>AI Assistants</h4>
            <p>Create intelligent chatbots with specialized knowledge and personalities</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">💬</div>
            <h4>Real-time Chat</h4>
            <p>ChatGPT-style interface with streaming responses and advanced features</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">💳</div>
            <h4>Monetization</h4>
            <p>Built-in payment processing with Stripe, PayPal, and bank deposits</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🌍</div>
            <h4>Global Ready</h4>
            <p>Support for 50+ languages and international payment methods</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h4>Analytics</h4>
            <p>Comprehensive dashboard with user analytics and revenue tracking</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h4>Enterprise Security</h4>
            <p>Advanced security features and content moderation systems</p>
        </div>
    </div>
    
    <div class="alert alert-info">
        <strong>Installation Requirements:</strong>
        <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
            <li>PHP 8.1 or higher</li>
            <li>MySQL 8.0 or MariaDB 10.3+</li>
            <li>Node.js 16+ and NPM</li>
            <li>Composer (PHP dependency manager)</li>
            <li>OpenAI API key (required for AI functionality)</li>
        </ul>
    </div>
    
    <div class="alert alert-warning">
        <strong>Before You Begin:</strong>
        <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
            <li>Ensure you have database credentials ready</li>
            <li>Have your OpenAI API key available</li>
            <li>Make sure your server meets all requirements</li>
            <li>This process may take 5-10 minutes to complete</li>
        </ul>
    </div>

    <form method="get" class="installer-form">
        <input type="hidden" name="step" value="2">
        <div class="form-actions">
            <div></div> <!-- Spacer -->
            <button type="submit" class="btn btn-primary btn-large">
                🚀 Start Installation
            </button>
        </div>
    </form>
</div>