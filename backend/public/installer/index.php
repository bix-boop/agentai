<?php
// Proxy loader for Phoenix AI installer when document root is backend/public

$rootInstaller = __DIR__ . '/../../../installer';
$reqPath = isset($_GET['path']) ? trim($_GET['path'], '/\\') : '';

if ($reqPath !== '') {
    $file = realpath($rootInstaller . '/' . $reqPath);
    if ($file && str_starts_with($file, realpath($rootInstaller)) && is_file($file)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file) ?: 'application/octet-stream';
        finfo_close($finfo);
        header('Content-Type: ' . $mime);
        readfile($file);
        exit;
    }
}

// Fall back to including the main installer
require_once $rootInstaller . '/index.php';