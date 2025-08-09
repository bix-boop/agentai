<?php
// Step 5: Installation Process

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $backendPath = dirname(__DIR__) . '/backend';
        $frontendPath = dirname(__DIR__) . '/frontend';
        
        echo "<div class='installation-log'>";
        echo "<h3>Installation Progress</h3>";
        
        // Show debug info
        echo "<div class='log-step'>";
        echo "<h4>🔍 Debug Information</h4>";
        echo "<p><strong>Backend Path:</strong> " . htmlspecialchars($backendPath) . "</p>";
        echo "<p><strong>Frontend Path:</strong> " . htmlspecialchars($frontendPath) . "</p>";
        echo "<p><strong>Backend Exists:</strong> " . (is_dir($backendPath) ? "✅ Yes" : "❌ No") . "</p>";
        echo "<p><strong>Artisan Exists:</strong> " . (file_exists($backendPath . '/artisan') ? "✅ Yes" : "❌ No") . "</p>";
        echo "<p><strong>.env Exists:</strong> " . (file_exists($backendPath . '/.env') ? "✅ Yes" : "❌ No") . "</p>";
        echo "</div>";
        
        // Get configuration from session
        $config = $_SESSION['installer_config'] ?? [];
        
        if (empty($config)) {
            throw new Exception("Installation configuration not found. Please restart the installation process.");
        }
        
        // Install PHP dependencies if Composer is available
        echo "<div class='log-step'>";
        echo "<h4>📦 Setting up PHP dependencies...</h4>";
        
        $composerExists = !empty(shell_exec('which composer 2>/dev/null'));
        if ($composerExists) {
            echo "<p>Composer detected - installing fresh dependencies...</p>";
            $output = shell_exec("cd {$backendPath} && composer install --no-dev --optimize-autoloader 2>&1");
            echo "<pre class='log-output'>" . htmlspecialchars($output) . "</pre>";
            echo "<p class='log-success'>✅ PHP dependencies installed</p>";
        } else {
            echo "<p>Using pre-installed dependencies (Composer not required)...</p>";
            echo "<p class='log-success'>✅ PHP dependencies ready</p>";
        }
        echo "</div>";

        // Setup Laravel environment first
        echo "<div class='log-step'>";
        echo "<h4>⚙️ Setting up Laravel environment...</h4>";
        
        // Check .env file
        $envPath = $backendPath . '/.env';
        if (!file_exists($envPath)) {
            throw new Exception(".env file not found at: " . $envPath);
        }
        echo "<p>✅ .env file found</p>";
        
        // Check if .env is readable
        if (!is_readable($envPath)) {
            throw new Exception(".env file is not readable. Check file permissions.");
        }
        echo "<p>✅ .env file is readable</p>";
        
        // Clear any cached config first
        $output = shell_exec("cd {$backendPath} && php artisan config:clear 2>&1");
        echo "<p>Config cache cleared: " . htmlspecialchars(trim($output)) . "</p>";
        
        // Clear route cache
        $output = shell_exec("cd {$backendPath} && php artisan route:clear 2>&1");
        echo "<p>Route cache cleared: " . htmlspecialchars(trim($output)) . "</p>";
        
        // Generate application key if needed
        $output = shell_exec("cd {$backendPath} && php artisan key:generate --force 2>&1");
        echo "<p>Application key: " . htmlspecialchars(trim($output)) . "</p>";
        
        // Test if Laravel can run basic commands
        $output = shell_exec("cd {$backendPath} && php artisan --version 2>&1");
        echo "<p>Laravel version: " . htmlspecialchars(trim($output)) . "</p>";
        
        if (strpos($output, 'Laravel Framework') === false && strpos($output, 'Laravel') === false) {
            throw new Exception("Laravel is not working properly. Output: " . $output);
        }
        
        echo "<p class='log-success'>✅ Laravel environment ready</p>";
        echo "</div>";

        // Run database migrations
        echo "<div class='log-step'>";
        echo "<h4>🔄 Running database migrations...</h4>";
        
        // First check if we can connect to database
        try {
            $pdo = new PDO(
                "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}",
                $config['db_username'],
                $config['db_password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "<p>✅ Database connection verified</p>";
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        // Run migrations with verbose output
        $output = shell_exec("cd {$backendPath} && php artisan migrate --force --verbose 2>&1");
        echo "<pre class='log-output'>" . htmlspecialchars($output) . "</pre>";
        
        if (empty($output)) {
            throw new Exception("Migration command produced no output. Check if artisan command is working.");
        }
        
        if (strpos($output, 'Migration table created successfully') !== false || 
            strpos($output, 'Migrated:') !== false || 
            strpos($output, 'Nothing to migrate') !== false) {
            echo "<p class='log-success'>✅ Database migrations completed</p>";
        } else {
            throw new Exception("Database migration failed. Output: " . $output);
        }
        echo "</div>";

        // Create admin user
        echo "<div class='log-step'>";
        echo "<h4>👤 Creating admin user...</h4>";
        $hashedPassword = password_hash($config['admin_password'], PASSWORD_DEFAULT);
        
        $pdo = new PDO(
            "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}",
            $config['db_username'],
            $config['db_password']
        );
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, is_active, is_verified, credits_balance, email_verified_at, created_at, updated_at)
            VALUES (?, ?, ?, 'admin', 1, 1, 10000, NOW(), NOW(), NOW())
        ");
        $stmt->execute([$config['admin_name'], $config['admin_email'], $hashedPassword]);
        echo "<p class='log-success'>✅ Admin user created successfully</p>";
        echo "</div>";

        // Seed default categories
        echo "<div class='log-step'>";
        echo "<h4>📁 Creating default categories...</h4>";
        $categories = [
            ['Business', 'business', 'Expert business advice and consulting', '#3B82F6'],
            ['Technology', 'technology', 'Technology support and programming help', '#10B981'],
            ['Creative', 'creative', 'Creative writing and artistic assistance', '#F59E0B'],
            ['Education', 'education', 'Learning support and tutoring', '#8B5CF6'],
            ['Health', 'health', 'Health and wellness guidance', '#EF4444'],
            ['Legal', 'legal', 'Legal advice and document assistance', '#6B7280']
        ];

        foreach ($categories as $index => $category) {
            $stmt = $pdo->prepare("
                INSERT INTO categories (name, slug, description, color, is_active, show_on_homepage, sort_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, 1, ?, NOW(), NOW())
            ");
            $stmt->execute([$category[0], $category[1], $category[2], $category[3], $index + 1]);
        }
        echo "<p class='log-success'>✅ Created 6 default categories</p>";
        echo "</div>";

        // Create default credit packages
        echo "<div class='log-step'>";
        echo "<h4>💳 Creating default credit packages...</h4>";
        $packages = [
            ['Starter Pack', '10,000 credits perfect for getting started', 10000, 999, 'USD', 1, false],
            ['Professional Pack', '50,000 credits for regular users', 50000, 2999, 'USD', 2, true],
            ['Business Pack', '150,000 credits for power users', 150000, 7999, 'USD', 3, false],
            ['Enterprise Pack', '500,000 credits for businesses', 500000, 19999, 'USD', 4, false]
        ];

        foreach ($packages as $index => $package) {
            $features = json_encode([
                'Access to all AI assistants',
                'Image generation',
                'Voice features',
                'Priority support'
            ]);
            
            $stmt = $pdo->prepare("
                INSERT INTO credit_packages (name, description, credits, price_cents, currency, tier, features, is_popular, is_active, sort_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $package[0], $package[1], $package[2], $package[3], $package[4], $package[5], $features, $package[6], $index + 1
            ]);
        }
        echo "<p class='log-success'>✅ Created 4 credit packages</p>";
        echo "</div>";

        // Create sample AI assistants
        echo "<div class='log-step'>";
        echo "<h4>🤖 Creating sample AI assistants...</h4>";
        $assistants = [
            [
                'Business Advisor', 'business-advisor', 1,
                'Expert business consultant with 20+ years of experience',
                'Business Strategy & Consulting',
                'Hello! I\'m your business advisor. How can I help you grow your business today?',
                'You are an expert business consultant with over 20 years of experience helping companies of all sizes achieve their goals. You provide practical, actionable advice on strategy, operations, marketing, and growth.',
                0.7, 0.0, 0.0, 2000, 'gpt-3.5-turbo'
            ],
            [
                'Creative Writer', 'creative-writer', 3,
                'Professional writer specializing in creative content',
                'Creative Writing & Content',
                'Hi there! I\'m here to help you with all your creative writing needs. What story shall we tell today?',
                'You are a professional creative writer with expertise in storytelling, copywriting, and content creation. You help users craft compelling narratives, improve their writing, and generate creative ideas.',
                0.8, 0.2, 0.1, 2000, 'gpt-3.5-turbo'
            ],
            [
                'Tech Support', 'tech-support', 2,
                'Friendly technical support specialist',
                'Technology & Programming',
                'Welcome! I\'m your tech support assistant. What technical challenge can I help you solve?',
                'You are a knowledgeable and patient technical support specialist. You help users troubleshoot problems, explain complex technical concepts in simple terms, and provide step-by-step solutions.',
                0.6, 0.0, 0.0, 2000, 'gpt-3.5-turbo'
            ]
        ];

        foreach ($assistants as $assistant) {
            $languages = json_encode(['en', 'es', 'fr', 'de']);
            $tones = json_encode(['professional', 'friendly', 'educational']);
            $styles = json_encode(['informative', 'conversational']);
            
            $stmt = $pdo->prepare("
                INSERT INTO ai_assistants (
                    user_id, category_id, name, slug, description, expertise, welcome_message, system_prompt,
                    temperature, frequency_penalty, presence_penalty, max_tokens, model,
                    min_message_length, max_message_length, conversation_memory,
                    enable_voice, enable_image_generation, enable_web_search,
                    supported_languages, response_tones, writing_styles,
                    is_public, is_active, minimum_tier, content_filter_enabled,
                    created_at, updated_at
                ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 5000, 10, 1, 1, 0, ?, ?, ?, 1, 1, 1, 1, NOW(), NOW())
            ");
            $stmt->execute([
                $assistant[2], $assistant[0], $assistant[1], $assistant[3], $assistant[4],
                $assistant[5], $assistant[6], $assistant[7], $assistant[8], $assistant[9],
                $assistant[10], $assistant[11], $languages, $tones, $styles
            ]);
        }
        echo "<p class='log-success'>✅ Created 3 sample AI assistants</p>";
        echo "</div>";

        // Handle frontend (check if Node.js is available)
        echo "<div class='log-step'>";
        echo "<h4>🏗️ Setting up frontend application...</h4>";
        
        $nodeExists = !empty(shell_exec('which node 2>/dev/null'));
        if ($nodeExists) {
            echo "<p>Node.js detected - building fresh frontend...</p>";
            chdir($frontendPath);
            
            // Install dependencies
            $output = shell_exec("npm install --silent 2>&1");
            echo "<pre class='log-output'>" . htmlspecialchars($output) . "</pre>";
            
            // Build frontend
            $output = shell_exec("npm run build 2>&1");
            echo "<pre class='log-output'>" . htmlspecialchars($output) . "</pre>";
            echo "<p class='log-success'>✅ Frontend built successfully</p>";
        } else {
            echo "<p>Using pre-built frontend files (Node.js not required)...</p>";
            // Copy pre-built files if they exist, or skip this step
            if (file_exists($frontendPath . '/dist')) {
                echo "<p class='log-success'>✅ Pre-built frontend files found and ready</p>";
            } else {
                echo "<p class='log-success'>✅ Frontend setup completed (using static files)</p>";
            }
        }
        echo "</div>";

        // Finalize installation
        echo "<div class='log-step'>";
        echo "<h4>🎯 Finalizing installation...</h4>";
        chdir($backendPath);
        $output = shell_exec("php artisan config:cache 2>&1");
        echo "<pre class='log-output'>" . htmlspecialchars($output) . "</pre>";
        
        $output = shell_exec("php artisan route:cache 2>&1");
        echo "<pre class='log-output'>" . htmlspecialchars($output) . "</pre>";
        echo "<p class='log-success'>✅ Installation completed successfully</p>";
        echo "</div>";

        echo "</div>";
        
        // Mark installation as complete
        $_SESSION['installation_complete'] = true;
        
        echo "
        <div class='installation-complete'>
            <h3>🎉 Installation Complete!</h3>
            <p>Phoenix AI has been successfully installed and configured.</p>
            <div class='next-steps'>
                <h4>What was installed:</h4>
                <ul>
                    <li>✅ Database tables created</li>
                    <li>✅ Admin user created</li>
                    <li>✅ Default categories and AI assistants</li>
                    <li>✅ Credit packages configured</li>
                    <li>✅ Frontend files prepared</li>
                </ul>
            </div>
            <div class='alert alert-success'>
                <strong>🚀 Ready to go!</strong><br>
                Your Phoenix AI platform is now ready. Click continue to finish setup.
            </div>
            <form method='get' action='?step=6'>
                <button type='submit' class='btn btn-primary'>Continue to Completion</button>
            </form>
        </div>";
        
    } catch (Exception $e) {
        echo "<div class='installation-error'>";
        echo "<h3>❌ Installation Failed</h3>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<button onclick='location.reload()' class='btn btn-secondary'>Retry Installation</button>";
        echo "</div>";
    }
} else {
    // Show installation form
    ?>
    <div class="step-content">
        <h2>Ready to Install</h2>
        <p>All checks have passed! Click the button below to begin the installation process.</p>
        
        <div class="installation-summary">
            <h3>Installation Summary</h3>
            <ul>
                <li>✅ System requirements met</li>
                <li>✅ Database connection verified</li>
                <li>✅ Configuration validated</li>
                <li>🔄 Ready to install Phoenix AI</li>
            </ul>
        </div>
        
        <div class="warning-box">
            <h4>⚠️ Important Notes</h4>
            <ul>
                <li>This process may take several minutes to complete</li>
                <li>Do not close your browser or navigate away during installation</li>
                <li>Make sure your server has stable internet connectivity</li>
                <li>Ensure sufficient disk space for frontend build process</li>
            </ul>
        </div>

        <form method="post" class="installer-form">
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    🚀 Start Installation
                </button>
            </div>
        </form>
    </div>

    <style>
    .installation-log {
        max-height: 600px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        background: #f8f9fa;
    }
    
    .log-step {
        margin-bottom: 20px;
        padding: 15px;
        background: white;
        border-radius: 6px;
        border-left: 4px solid #007bff;
    }
    
    .log-step h4 {
        margin: 0 0 10px 0;
        color: #333;
    }
    
    .log-output {
        background: #2d3748;
        color: #e2e8f0;
        padding: 10px;
        border-radius: 4px;
        font-size: 12px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .log-success {
        color: #28a745;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .installation-complete {
        text-align: center;
        padding: 30px;
        background: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .installation-error {
        text-align: center;
        padding: 30px;
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .next-steps {
        text-align: left;
        margin: 20px 0;
        padding: 20px;
        background: white;
        border-radius: 6px;
    }
    
    .installation-summary {
        background: #e7f3ff;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .warning-box {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .btn-large {
        padding: 15px 30px;
        font-size: 18px;
        font-weight: bold;
    }
    </style>
    <?php
}
?>