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
    '../backend/storage' => is_writable('../backend/storage'),
    '../backend/bootstrap/cache' => is_writable('../backend/bootstrap/cache'),
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

// Check for Composer
$composerExists = !empty(shell_exec('which composer 2>/dev/null'));
$requirements['Composer'] = [
    'required' => 'Installed',
    'current' => $composerExists ? 'Available' : 'Not Found',
    'passed' => $composerExists
];

// Check for Node.js and NPM
$nodeExists = !empty(shell_exec('which node 2>/dev/null'));
$npmExists = !empty(shell_exec('which npm 2>/dev/null'));
$requirements['Node.js'] = [
    'required' => '16.0+',
    'current' => $nodeExists ? trim(shell_exec('node --version 2>/dev/null')) : 'Not Found',
    'passed' => $nodeExists
];
$requirements['NPM'] = [
    'required' => 'Installed',
    'current' => $npmExists ? trim(shell_exec('npm --version 2>/dev/null')) : 'Not Found',
    'passed' => $npmExists
];

$allRequirementsPassed = $extensionsPassed && $permissionsPassed && $composerExists && $nodeExists && $npmExists;
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

            <!-- Composer -->
            <li class="<?= $requirements['Composer']['passed'] ? 'passed' : 'failed' ?>">
                <div>
                    <strong>Composer</strong><br>
                    <small>Required for PHP dependencies</small>
                </div>
                <div>
                    <span class="requirement-status">
                        <?= $requirements['Composer']['passed'] ? '✅' : '❌' ?>
                    </span>
                    <small><?= $requirements['Composer']['current'] ?></small>
                </div>
            </li>

            <!-- Node.js -->
            <li class="<?= $requirements['Node.js']['passed'] ? 'passed' : 'failed' ?>">
                <div>
                    <strong>Node.js</strong><br>
                    <small>Required for frontend build</small>
                </div>
                <div>
                    <span class="requirement-status">
                        <?= $requirements['Node.js']['passed'] ? '✅' : '❌' ?>
                    </span>
                    <small><?= $requirements['Node.js']['current'] ?></small>
                </div>
            </li>

            <!-- NPM -->
            <li class="<?= $requirements['NPM']['passed'] ? 'passed' : 'failed' ?>">
                <div>
                    <strong>NPM</strong><br>
                    <small>Required for frontend dependencies</small>
                </div>
                <div>
                    <span class="requirement-status">
                        <?= $requirements['NPM']['passed'] ? '✅' : '❌' ?>
                    </span>
                    <small><?= $requirements['NPM']['current'] ?></small>
                </div>
            </li>
        </ul>
    </div>

    <?php if (!$allRequirementsPassed): ?>
        <div class="alert alert-warning">
            <strong>How to Fix Requirements:</strong>
            <ul style="margin-top: 10px; list-style: disc; margin-left: 20px;">
                <?php if (!$extensionsPassed): ?>
                    <li>Install missing PHP extensions through your hosting control panel or contact your hosting provider</li>
                <?php endif; ?>
                <?php if (!$permissionsPassed): ?>
                    <li>Set proper file permissions: <code>chmod -R 755 storage bootstrap/cache</code></li>
                <?php endif; ?>
                <?php if (!$composerExists): ?>
                    <li>Install Composer: <a href="https://getcomposer.org/download/" target="_blank">https://getcomposer.org/download/</a></li>
                <?php endif; ?>
                <?php if (!$nodeExists): ?>
                    <li>Install Node.js: <a href="https://nodejs.org/" target="_blank">https://nodejs.org/</a></li>
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