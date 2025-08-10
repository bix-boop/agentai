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
     * Stream a long-running command's output to the browser in real-time.
     */
    public static function execAndStream(string $command, ?string $workingDir = null): int {
        if ($workingDir && is_dir($workingDir)) {
            $originalDir = getcwd();
            chdir($workingDir);
        }

        // Disable output buffering layers if possible
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('implicit_flush', '1');
        ob_implicit_flush(true);

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptorspec, $pipes);
        if (!is_resource($process)) {
            if (isset($originalDir)) { chdir($originalDir); }
            echo htmlspecialchars("Failed to start process: $command") . "\n";
            return -1;
        }

        // Non-blocking streams
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        echo "<pre class='log-output'>";
        while (true) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            if ($stdout !== '') {
                echo htmlspecialchars($stdout);
                flush();
            }
            if ($stderr !== '') {
                echo htmlspecialchars($stderr);
                flush();
            }

            $status = proc_get_status($process);
            if ($status['running'] === false) {
                break;
            }
            usleep(100000); // 100ms
        }
        echo "</pre>";

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if (isset($originalDir)) {
            chdir($originalDir);
        }
        return $exitCode;
    }

    /**
     * Stream artisan command output in real-time.
     */
    public static function execArtisanStream(string $command, string $backendPath): int {
        if (!file_exists($backendPath . '/artisan')) {
            throw new Exception("Laravel artisan not found at: $backendPath/artisan");
        }
        $phpPath = self::detectPHPPath();
        return self::execAndStream("$phpPath artisan $command 2>&1", $backendPath);
    }
    
    /**
     * Check if Composer is available
     */
    public static function checkComposer() {
        // Try system composer
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
     * Ensure a usable composer command. If system composer is not available,
     * attempt to download composer.phar into the backend directory and return the command to invoke it.
     * Returns an array [composerCommand, workingDir]
     */
    public static function ensureComposer(string $backendPath): array
    {
        $systemComposer = self::checkComposer();
        if ($systemComposer) {
            return [$systemComposer, $backendPath];
        }

        // Try local composer.phar in backend
        $composerPhar = $backendPath . '/composer.phar';
        if (!file_exists($composerPhar)) {
            // Attempt to download composer-stable.phar
            try {
                $composerUrl = 'https://getcomposer.org/composer-stable.phar';
                $pharData = @file_get_contents($composerUrl);
                if ($pharData === false) {
                    throw new \Exception('Unable to download composer.phar');
                }
                if (@file_put_contents($composerPhar, $pharData) === false) {
                    throw new \Exception('Failed to save composer.phar at ' . $composerPhar);
                }
            } catch (\Exception $e) {
                throw new \Exception('Composer not available and download failed: ' . $e->getMessage());
            }
        }

        // Return command to run composer.phar with detected PHP
        $phpPath = self::detectPHPPath();
        return ["$phpPath $composerPhar", $backendPath];
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