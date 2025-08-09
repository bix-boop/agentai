<?php
/**
 * Enhanced Error Handler for Phoenix AI
 * Provides comprehensive error logging and debugging capabilities
 */

class ErrorHandler {
    private static $logFile = null;
    
    public static function init() {
        self::$logFile = dirname(__DIR__, 2) . '/installer_debug.log';
        
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }
    
    public static function handleError($severity, $message, $file, $line) {
        $errorType = self::getErrorType($severity);
        $logMessage = "[$errorType] $message in $file on line $line";
        
        self::log($logMessage);
        
        // Display error if in debug mode
        if (ini_get('display_errors')) {
            echo "<div style='background:#f8d7da;padding:10px;margin:10px 0;border-radius:5px;'>";
            echo "<strong>$errorType:</strong> " . htmlspecialchars($message);
            echo "<br><small>File: " . htmlspecialchars($file) . " Line: $line</small>";
            echo "</div>";
        }
    }
    
    public static function handleException($exception) {
        $logMessage = "[EXCEPTION] " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
        $logMessage .= "\nStack trace:\n" . $exception->getTraceAsString();
        
        self::log($logMessage);
        
        // Display exception
        echo "<div style='background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;border-left:4px solid #dc3545;'>";
        echo "<h4 style='margin:0 0 10px 0;color:#721c24;'>🚨 Installation Error</h4>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        
        if (ini_get('display_errors')) {
            echo "<details style='margin-top:10px;'>";
            echo "<summary>Stack Trace</summary>";
            echo "<pre style='background:#f5f5f5;padding:10px;margin:5px 0;border-radius:3px;font-size:12px;'>";
            echo htmlspecialchars($exception->getTraceAsString());
            echo "</pre>";
            echo "</details>";
        }
        
        echo "<div style='margin-top:15px;'>";
        echo "<a href='/debug.php' style='background:#007bff;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin-right:10px;'>🔍 Debug Info</a>";
        echo "<a href='/installer/' style='background:#28a745;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>🔄 Restart Installation</a>";
        echo "</div>";
        echo "</div>";
    }
    
    public static function log($message, $echo = false) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        
        // Write to log file
        if (self::$logFile) {
            file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        if ($echo) {
            echo "<p style='font-family:monospace;'>" . htmlspecialchars($logEntry) . "</p>";
        }
    }
    
    public static function getLogContents() {
        if (self::$logFile && file_exists(self::$logFile)) {
            return file_get_contents(self::$logFile);
        }
        return "No log file found.";
    }
    
    public static function clearLog() {
        if (self::$logFile && file_exists(self::$logFile)) {
            unlink(self::$logFile);
        }
    }
    
    private static function getErrorType($severity) {
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
                return 'FATAL ERROR';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'WARNING';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'NOTICE';
            case E_STRICT:
                return 'STRICT';
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'DEPRECATED';
            default:
                return 'UNKNOWN';
        }
    }
    
    public static function testPhoenixSystem() {
        $results = [];
        
        // Test 1: PHP CLI availability
        try {
            $phpPath = PHPUtils::detectPHPPath();
            $results['php_cli'] = ['status' => 'PASS', 'message' => "PHP CLI found at: $phpPath"];
        } catch (Exception $e) {
            $results['php_cli'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
        
        // Test 2: Database connection
        try {
            $envFile = dirname(__DIR__, 2) . '/backend/.env';
            if (file_exists($envFile)) {
                $env = file_get_contents($envFile);
                preg_match('/DB_HOST=(.*)/', $env, $host);
                preg_match('/DB_DATABASE=(.*)/', $env, $db);
                preg_match('/DB_USERNAME=(.*)/', $env, $user);
                preg_match('/DB_PASSWORD=(.*)/', $env, $pass);
                
                if (isset($host[1], $db[1], $user[1])) {
                    $pdo = new PDO("mysql:host={$host[1]};dbname={$db[1]}", $user[1], $pass[1] ?? '');
                    $results['database'] = ['status' => 'PASS', 'message' => 'Database connection successful'];
                } else {
                    $results['database'] = ['status' => 'FAIL', 'message' => 'Database configuration incomplete'];
                }
            } else {
                $results['database'] = ['status' => 'FAIL', 'message' => '.env file not found'];
            }
        } catch (Exception $e) {
            $results['database'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
        
        // Test 3: Laravel functionality
        try {
            $backendPath = dirname(__DIR__, 2) . '/backend';
            if (file_exists($backendPath . '/artisan')) {
                $output = PHPUtils::execArtisan("--version", $backendPath);
                if (strpos($output, 'Laravel') !== false) {
                    $results['laravel'] = ['status' => 'PASS', 'message' => trim($output)];
                } else {
                    $results['laravel'] = ['status' => 'FAIL', 'message' => 'Laravel not responding properly'];
                }
            } else {
                $results['laravel'] = ['status' => 'FAIL', 'message' => 'Laravel artisan not found'];
            }
        } catch (Exception $e) {
            $results['laravel'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
        
        return $results;
    }
}