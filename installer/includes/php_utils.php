<?php
/**
 * PHP Utilities for Phoenix AI Installer
 * Handles PHP path detection and command execution in various hosting environments
 */

class PHPUtils {
    private static $phpPath = null;
    
    /**
     * Detect the correct PHP binary path
     */
    public static function detectPHPPath() {
        if (self::$phpPath !== null) {
            return self::$phpPath;
        }
        
        // Common PHP binary locations
        $possiblePaths = [
            'php',                                    // Standard PATH
            '/usr/bin/php',                          // Common Linux location
            '/usr/local/bin/php',                    // Alternative Linux location
            '/opt/plesk/php/8.3/bin/php',           // Plesk PHP 8.3
            '/opt/plesk/php/8.2/bin/php',           // Plesk PHP 8.2
            '/opt/plesk/php/8.1/bin/php',           // Plesk PHP 8.1
            '/opt/alt/php83/usr/bin/php',           // CloudLinux PHP 8.3
            '/opt/alt/php82/usr/bin/php',           // CloudLinux PHP 8.2
            '/opt/alt/php81/usr/bin/php',           // CloudLinux PHP 8.1
            '/usr/local/php83/bin/php',             // Custom PHP 8.3
            '/usr/local/php82/bin/php',             // Custom PHP 8.2
            '/usr/local/php81/bin/php',             // Custom PHP 8.1
        ];
        
        foreach ($possiblePaths as $path) {
            if (self::testPHPPath($path)) {
                self::$phpPath = $path;
                return $path;
            }
        }
        
        // If no PHP found, try to get current PHP binary
        $currentPHP = PHP_BINARY;
        if (!empty($currentPHP) && self::testPHPPath($currentPHP)) {
            self::$phpPath = $currentPHP;
            return $currentPHP;
        }
        
        throw new Exception("PHP CLI binary not found. Please ensure PHP is installed and accessible.");
    }
    
    /**
     * Test if a PHP path works
     */
    private static function testPHPPath($path) {
        $output = @shell_exec("$path --version 2>/dev/null");
        return !empty($output) && strpos($output, 'PHP') !== false;
    }
    
    /**
     * Execute a PHP command with proper path detection
     */
    public static function execPHP($command, $workingDir = null) {
        $phpPath = self::detectPHPPath();
        
        if ($workingDir && is_dir($workingDir)) {
            $originalDir = getcwd();
            chdir($workingDir);
        }
        
        $fullCommand = $phpPath . ' ' . $command . ' 2>&1';
        $output = shell_exec($fullCommand);
        
        if (isset($originalDir)) {
            chdir($originalDir);
        }
        
        return $output;
    }
    
    /**
     * Execute Laravel artisan command
     */
    public static function execArtisan($command, $backendPath) {
        if (!file_exists($backendPath . '/artisan')) {
            throw new Exception("Laravel artisan not found at: $backendPath/artisan");
        }
        
        return self::execPHP("artisan $command", $backendPath);
    }
    
    /**
     * Get PHP version using detected binary
     */
    public static function getPHPVersion() {
        try {
            $phpPath = self::detectPHPPath();
            $output = shell_exec("$phpPath --version 2>&1");
            if (preg_match('/PHP (\d+\.\d+\.\d+)/', $output, $matches)) {
                return $matches[1];
            }
        } catch (Exception $e) {
            // Fallback to web PHP version
        }
        
        return PHP_VERSION;
    }
    
    /**
     * Check if Composer is available
     */
    public static function checkComposer() {
        $paths = ['composer', '/usr/local/bin/composer', '/usr/bin/composer'];
        
        foreach ($paths as $path) {
            $output = @shell_exec("$path --version 2>/dev/null");
            if (!empty($output) && strpos($output, 'Composer') !== false) {
                return $path;
            }
        }
        
        return false;
    }
    
    /**
     * Log message with timestamp
     */
    public static function log($message, $echo = true) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message";
        
        if ($echo) {
            echo "<p>" . htmlspecialchars($logMessage) . "</p>";
            flush();
        }
        
        return $logMessage;
    }
}