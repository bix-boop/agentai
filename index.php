<?php
// Phoenix AI - Main Entry Point

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if installation is complete
$envPath = __DIR__ . '/backend/.env';
if (!file_exists($envPath)) {
    // Check if this is a health check request
    if (strpos($_SERVER['REQUEST_URI'], '/health') !== false) {
        header('Location: /health_check.php');
        exit;
    }
    
    // Redirect to installer if not installed
    header('Location: /installer/');
    exit;
}

// Get the request URI and clean it
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Handle special utility routes
if ($requestPath === '/health' || $requestPath === '/health-check') {
    header('Location: /health_check.php');
    exit;
}

if ($requestPath === '/system-test') {
    header('Location: /test_system.php');
    exit;
}

if ($requestPath === '/debug') {
    header('Location: /debug.php');
    exit;
}

if ($requestPath === '/setup-vendor') {
    header('Location: /setup_vendor.php');
    exit;
}

if ($requestPath === '/verify-installation') {
    header('Location: /verify_installation.php');
    exit;
}

if ($requestPath === '/final-optimization') {
    header('Location: /final_optimization.php');
    exit;
}

if ($requestPath === '/test-complete-platform') {
    header('Location: /test_complete_platform.php');
    exit;
}

// Handle API requests
if (strpos($requestPath, '/api/') === 0) {
    // Check if backend is accessible
    if (!file_exists(__DIR__ . '/backend/public/index.php')) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Backend not available. Please complete installation.',
            'redirect' => '/installer/'
        ]);
        exit;
    }
    
    // Set up environment for Laravel
    $_SERVER['SCRIPT_NAME'] = '/backend/public/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/backend/public/index.php';
    
    // Include Laravel
    require_once __DIR__ . '/backend/public/index.php';
    exit;
}

// Handle admin requests
if (strpos($requestPath, '/admin') === 0) {
    // Serve the React admin app (or fallback)
    if (file_exists(__DIR__ . '/frontend/dist/index.html')) {
        header('Content-Type: text/html');
        readfile(__DIR__ . '/frontend/dist/index.html');
    } else {
        // Fallback admin login
        header('Content-Type: text/html');
        include __DIR__ . '/frontend/public/index.html';
    }
    exit;
}

// Handle static files
if (strpos($requestPath, '/frontend/') === 0 || strpos($requestPath, '/backend/') === 0) {
    $filePath = __DIR__ . $requestPath;
    if (file_exists($filePath) && is_file($filePath)) {
        // Serve static file with correct MIME type
        $mimeType = mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
        exit;
    }
}

// For all other requests, serve the main frontend
if (file_exists(__DIR__ . '/frontend/dist/index.html')) {
    header('Content-Type: text/html');
    readfile(__DIR__ . '/frontend/dist/index.html');
} else {
    // Fallback to simple HTML landing page
    header('Content-Type: text/html');
    include __DIR__ . '/frontend/public/index.html';
}
?>