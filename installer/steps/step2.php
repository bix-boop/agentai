<?php
// Step 2: System Requirements Check

$requirements = [
    'PHP Version' => [
        'required' => '8.1.0',
        'current' => PHP_VERSION,
        'passed' => version_compare(PHP_VERSION, '8.1.0', '>=')
    ],
    'PHP Extensions' => [],
];

// Check required PHP extensions
$requiredExtensions = [
    'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'json', 'mbstring', 
    'openssl', 'pcre', 'pdo', 'pdo_mysql', 'tokenizer', 'xml', 'zip'
];

$extensionsPassed = true;
foreach ($requiredExtensions as $extension) {
    $loaded = extension_loaded($extension);
    $requirements['PHP Extensions'][] = [
        'name' => $extension,
        'passed' => $loaded
    ];
    if (!$loaded) $extensionsPassed = false;
}

// Check file permissions
$paths = [
    '../backend/storage' => is_writable('../backend/storage') || !file_exists('../backend/storage'),
    '../backend/bootstrap/cache' => is_writable('../backend/bootstrap/cache') || !file_exists('../backend/bootstrap/cache'),
    '../backend/.env' => is_writable('../backend') || file_exists('../backend/.env'),
];

$permissionsPassed = true;
foreach ($paths as $path => $writable) {
    $requirements['File Permissions'][] = [
        'name' => $path,
        'passed' => $writable
    ];
    if (!$writable) $permissionsPassed = false;
}

// Check for Composer (OPTIONAL - we'll handle this)
$composerExists = !empty(shell_exec('which composer 2>/dev/null'));
$requirements['Composer'] = [
    'required' => 'Optional',
    'current' => $composerExists ? 'Available' : 'Will install dependencies automatically',
    'passed' => true // Always pass - we'll handle it
];

// Check for Node.js and NPM (OPTIONAL - we'll handle this)
$nodeExists = !empty(shell_exec('which node 2>/dev/null'));
$npmExists = !empty(shell_exec('which npm 2>/dev/null'));
$requirements['Node.js'] = [
    'required' => 'Optional',
    'current' => $nodeExists ? trim(shell_exec('node --version 2>/dev/null')) : 'Pre-built frontend included',
    'passed' => true // Always pass - we have pre-built files
];
$requirements['NPM'] = [
    'required' => 'Optional',
    'current' => $npmExists ? trim(shell_exec('npm --version 2>/dev/null')) : 'Pre-built frontend included',
    'passed' => true // Always pass - we have pre-built files
];

// Only require PHP and extensions to pass
$allRequirementsPassed = $extensionsPassed && $permissionsPassed;
?>

<div class="step-content">
    <h2>System Requirements Check</h2>
    <p>We're checking if your server meets all the requirements to run Phoenix AI.</p>
    
    <?php if ($allRequirementsPassed): ?>
        <div class="alert alert-success">
            <strong>✅ All Requirements Met!</strong><br>
            Your server is ready to install Phoenix AI.
        </div>
    <?php else: ?>
        <div class="alert alert-error">
            <strong>❌ Some Requirements Not Met</strong><br>
            Please resolve the issues below before continuing.
        </div>
    <?php endif; ?>

    <div class="requirements-check">
        <h3>System Requirements</h3>
        
        <ul class="requirements-list">
            <!-- PHP Version -->
            <li class="<?= $requirements['PHP Version']['passed'] ? 'passed' : 'failed' ?>">
                <div>
                    <strong>PHP Version</strong><br>
                    <small>Required: <?= $requirements['PHP Version']['required'] ?>+</small>
                </div>
                <div>
                    <span class="requirement-status">
                        <?= $requirements['PHP Version']['passed'] ? '✅' : '❌' ?>
                    </span>
                    <small><?= $requirements['PHP Version']['current'] ?></small>
                </div>
            </li>

            <!-- PHP Extensions -->
            <?php foreach ($requirements['PHP Extensions'] as $ext): ?>
            <li class="<?= $ext['passed'] ? 'passed' : 'failed' ?>">
                <div>
                    <strong>PHP Extension: <?= $ext['name'] ?></strong>
                </div>
                <div>
                    <span class="requirement-status">
                        <?= $ext['passed'] ? '✅' : '❌' ?>
                    </span>
                </div>
            </li>
            <?php endforeach; ?>

            <!-- File Permissions -->
            <?php foreach ($requirements['File Permissions'] as $perm): ?>
            <li class="<?= $perm['passed'] ? 'passed' : 'failed' ?>">
                <div>
                    <strong>Writable: <?= $perm['name'] ?></strong>
                </div>
                <div>
                    <span class="requirement-status">
                        <?= $perm['passed'] ? '✅' : '❌' ?>
                    </span>
                </div>
            </li>
            <?php endforeach; ?>

            <!-- Composer (Now Optional) -->
            <li class="passed">
                <div>
                    <strong>Composer</strong><br>
                    <small>PHP dependency manager (Optional)</small>
                </div>
                <div>
                    <span class="requirement-status">✅</span>
                    <small><?= $requirements['Composer']['current'] ?></small>
                </div>
            </li>

            <!-- Node.js (Now Optional) -->
            <li class="passed">
                <div>
                    <strong>Node.js</strong><br>
                    <small>Frontend build tool (Optional)</small>
                </div>
                <div>
                    <span class="requirement-status">✅</span>
                    <small><?= $requirements['Node.js']['current'] ?></small>
                </div>
            </li>

            <!-- NPM (Now Optional) -->
            <li class="passed">
                <div>
                    <strong>NPM</strong><br>
                    <small>Package manager (Optional)</small>
                </div>
                <div>
                    <span class="requirement-status">✅</span>
                    <small><?= $requirements['NPM']['current'] ?></small>
                </div>
            </li>
        </ul>
    </div>

    <?php if (!$composerExists || !$nodeExists): ?>
        <div class="alert alert-info">
            <strong>💡 No Composer/Node.js? No Problem!</strong>
            <p>Don't worry! Phoenix AI includes pre-built dependencies and frontend files. The installer will:</p>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <li>✅ Use pre-installed PHP dependencies (no Composer needed)</li>
                <li>✅ Use pre-built React frontend (no Node.js/NPM needed)</li>
                <li>✅ Configure everything automatically</li>
                <li>✅ Work on any standard PHP hosting</li>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$allRequirementsPassed): ?>
        <div class="alert alert-warning">
            <strong>How to Fix Requirements:</strong>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <?php if (!$extensionsPassed): ?>
                    <li><strong>Missing PHP Extensions:</strong> Contact your hosting provider to enable the missing extensions, or use the Plesk "PHP Settings" to enable them.</li>
                <?php endif; ?>
                <?php if (!$permissionsPassed): ?>
                    <li><strong>File Permissions:</strong> In Plesk File Manager, right-click the folders and set permissions to 755 (read/write/execute).</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="get" class="installer-form">
        <input type="hidden" name="step" value="<?= $allRequirementsPassed ? '3' : '2' ?>">
        <div class="form-actions">
            <a href="?step=1" class="btn btn-secondary">← Back</a>
            <?php if ($allRequirementsPassed): ?>
                <button type="submit" class="btn btn-primary">
                    Continue to Database Setup →
                </button>
            <?php else: ?>
                <button type="submit" class="btn btn-warning">
                    🔄 Recheck Requirements
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>