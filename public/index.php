<?php

declare(strict_types=1);

/**
 * AdminKit - Public Entry Point
 * 
 * This file serves as the main entry point for the AdminKit application.
 * It sets up the application environment, loads dependencies, and handles routing.
 */

// Performance monitoring
$startTime = microtime(true);

// Define application root
define('ADMINKIT_ROOT', dirname(__DIR__));

// Load Composer autoloader
require_once ADMINKIT_ROOT . '/vendor/autoload.php';

// Check if installation is needed
if (!file_exists(ADMINKIT_ROOT . '/.env') && !file_exists(ADMINKIT_ROOT . '/config/app.php')) {
    // Redirect to installation wizard
    if (strpos($_SERVER['REQUEST_URI'], '/install') === false) {
        header('Location: /install/');
        exit;
    }
}

// Load environment variables if available
if (file_exists(ADMINKIT_ROOT . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ADMINKIT_ROOT);
    $dotenv->load();
}

// Error reporting based on environment
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

try {
    // Bootstrap the application
    $app = require ADMINKIT_ROOT . '/bootstrap/app.php';
    
    // Run the application
    $app->run();
    
} catch (Throwable $e) {
    // Handle fatal errors gracefully
    $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    
    if ($isDebug) {
        // Show detailed error in debug mode
        echo '<h1>AdminKit Error</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        echo '<h3>Stack Trace:</h3>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // Show generic error in production
        http_response_code(500);
        echo '<h1>Internal Server Error</h1>';
        echo '<p>The application encountered an unexpected error. Please try again later.</p>';
        
        // Log the error
        error_log('AdminKit Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }
}

// Performance info in debug mode
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    
    echo "<!-- AdminKit Performance: {$executionTime}ms, {$memoryUsage}MB -->";
}
