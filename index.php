<?php
// Phoenix AI - Main Entry Point

// Check if installation is complete
if (!file_exists(__DIR__ . '/backend/.env')) {
    // Redirect to installer if not installed
    header('Location: /installer/');
    exit;
}

// Check if this is an API request
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    // Forward to Laravel backend
    require_once __DIR__ . '/backend/public/index.php';
    exit;
}

// Check if this is an admin request
if (strpos($_SERVER['REQUEST_URI'], '/admin') === 0) {
    // Serve the React admin app (or fallback)
    if (file_exists(__DIR__ . '/frontend/dist/index.html')) {
        readfile(__DIR__ . '/frontend/dist/index.html');
    } else {
        // Fallback admin login
        include __DIR__ . '/frontend/public/index.html';
    }
    exit;
}

// For all other requests, serve the main frontend
if (file_exists(__DIR__ . '/frontend/dist/index.html')) {
    readfile(__DIR__ . '/frontend/dist/index.html');
} else {
    // Fallback to simple HTML landing page
    include __DIR__ . '/frontend/public/index.html';
}
?>