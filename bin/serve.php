#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * AdminKit Development Server
 * 
 * A simple development server script for AdminKit
 */

// Prevent running from web
if (php_sapi_name() !== 'cli') {
    exit('This script can only be run from the command line.');
}

// Define root directory
define('ADMINKIT_ROOT', dirname(__DIR__));

// Default configuration
$host = '127.0.0.1';
$port = 8000;
$docroot = ADMINKIT_ROOT . '/public';
$router = null;

// Parse command line arguments
$options = getopt('h:p:d:r:', ['host:', 'port:', 'docroot:', 'router:', 'help', 'version']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

if (isset($options['version'])) {
    echo "AdminKit Development Server v1.0.7\n";
    exit(0);
}

// Override defaults with command line options
$host = $options['h'] ?? $options['host'] ?? $host;
$port = (int)($options['p'] ?? $options['port'] ?? $port);
$docroot = $options['d'] ?? $options['docroot'] ?? $docroot;
$router = $options['r'] ?? $options['router'] ?? $router;

// Validate document root
if (!is_dir($docroot)) {
    echo "Error: Document root directory '$docroot' does not exist.\n";
    exit(1);
}

if (!is_file($docroot . '/index.php')) {
    echo "Error: index.php not found in document root '$docroot'.\n";
    exit(1);
}

// Check if port is available
if (!isPortAvailable($host, $port)) {
    echo "Error: Port $port is already in use on $host.\n";
    echo "Try using a different port with -p option.\n";
    exit(1);
}

// Create router script if not specified
if (!$router) {
    $router = createRouterScript();
}

// Display server information
echo "\n";
echo "AdminKit Development Server\n";
echo "===========================\n";
echo "Host:        $host\n";
echo "Port:        $port\n";
echo "Document Root: $docroot\n";
echo "Server URL:  http://$host:$port\n";
echo "\n";
echo "Press Ctrl+C to stop the server\n";
echo "\n";

// Setup signal handlers for graceful shutdown
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() {
        echo "\n\nShutting down development server...\n";
        cleanup();
        exit(0);
    });
    
    pcntl_signal(SIGTERM, function() {
        echo "\n\nShutting down development server...\n";
        cleanup();
        exit(0);
    });
}

// Start the built-in PHP server
$command = sprintf(
    'php -S %s:%d -t %s %s',
    escapeshellarg($host),
    $port,
    escapeshellarg($docroot),
    $router ? escapeshellarg($router) : ''
);

// Execute the server command
$process = popen($command, 'r');

if (!$process) {
    echo "Error: Failed to start PHP development server.\n";
    exit(1);
}

// Keep the script running and handle signals
while (!feof($process)) {
    $line = fgets($process);
    if ($line !== false) {
        echo $line;
    }
    
    // Handle signals if available
    if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
    }
    
    usleep(10000); // 10ms delay
}

pclose($process);
cleanup();

/**
 * Check if a port is available
 */
function isPortAvailable(string $host, int $port): bool
{
    $connection = @fsockopen($host, $port);
    
    if (is_resource($connection)) {
        fclose($connection);
        return false;
    }
    
    return true;
}

/**
 * Create a router script for the development server
 */
function createRouterScript(): string
{
    $routerPath = sys_get_temp_dir() . '/adminkit_router_' . getmypid() . '.php';
    
    $routerContent = <<<'PHP'
<?php

/**
 * AdminKit Development Server Router
 * 
 * This router handles requests for the PHP built-in server
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Log the request
$timestamp = date('Y-m-d H:i:s');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

echo "[$timestamp] $remoteAddr - $method $requestUri\n";

// Handle static files
if ($path !== '/' && file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    
    // Set appropriate content type for static files
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    
    return false; // Serve the file
}

// Route everything else to index.php
return false;
PHP;

    file_put_contents($routerPath, $routerContent);
    
    // Register cleanup
    register_shutdown_function(function() use ($routerPath) {
        if (file_exists($routerPath)) {
            unlink($routerPath);
        }
    });
    
    return $routerPath;
}

/**
 * Cleanup function
 */
function cleanup(): void
{
    // Clean up temporary router script
    $pattern = sys_get_temp_dir() . '/adminkit_router_*.php';
    foreach (glob($pattern) as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

/**
 * Show help information
 */
function showHelp(): void
{
    echo <<<HELP
AdminKit Development Server

USAGE:
    php bin/serve.php [OPTIONS]

OPTIONS:
    -h, --host=HOST      Server host (default: 127.0.0.1)
    -p, --port=PORT      Server port (default: 8000)
    -d, --docroot=PATH   Document root path (default: ./public)
    -r, --router=FILE    Router script file
    --help               Show this help message
    --version            Show version information

EXAMPLES:
    php bin/serve.php
    php bin/serve.php -h 0.0.0.0 -p 9000
    php bin/serve.php --host=localhost --port=8080
    php bin/serve.php -d /path/to/docroot

NOTES:
    - This server is for development only
    - Do not use in production environments
    - Press Ctrl+C to stop the server

HELP;
}
