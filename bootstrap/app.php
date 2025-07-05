<?php

declare(strict_types=1);

/**
 * AdminKit - Application Bootstrap
 * 
 * This file bootstraps the AdminKit application by setting up the container,
 * services, and returning a configured Slim application instance.
 */

use Slim\Factory\AppFactory;
use DI\Container;
use DI\ContainerBuilder;
use AdminKit\AdminKit;
use AdminKit\Services\ConfigService;

// Create DI container
$containerBuilder = new ContainerBuilder();

// Load container definitions
if (file_exists(ADMINKIT_ROOT . '/config/container.php')) {
    $containerBuilder->addDefinitions(ADMINKIT_ROOT . '/config/container.php');
}

// Build container
$container = $containerBuilder->build();

// Create Slim app with container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(
    ($_ENV['APP_DEBUG'] ?? 'false') === 'true', // Display error details
    true, // Log errors
    true  // Log error details
);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Add routing middleware
$app->addRoutingMiddleware();

// Set base path if running in subdirectory
$basePath = $_ENV['APP_BASE_PATH'] ?? '';
if (!empty($basePath)) {
    $app->setBasePath($basePath);
}

// Initialize AdminKit if configuration exists
if (file_exists(ADMINKIT_ROOT . '/.env') || file_exists(ADMINKIT_ROOT . '/config/app.php')) {
    try {
        // Load configuration
        $config = [];
        
        // Load from config file if exists
        if (file_exists(ADMINKIT_ROOT . '/config/app.php')) {
            $config = require ADMINKIT_ROOT . '/config/app.php';
        }
        
        // Override with environment variables
        $config = array_merge($config, [
            'app_name' => $_ENV['APP_NAME'] ?? 'AdminKit',
            'app_url' => $_ENV['APP_URL'] ?? 'http://localhost',
            'app_debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            'app_timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
            'app_locale' => $_ENV['APP_LOCALE'] ?? 'en',
            'route_prefix' => $_ENV['ADMINKIT_ROUTE_PREFIX'] ?? '/admin',
            'brand_name' => $_ENV['ADMINKIT_BRAND_NAME'] ?? 'AdminKit',
        ]);
        
        // Initialize AdminKit
        $adminKit = new AdminKit($app, $config);
        
        // Store AdminKit instance in container
        $container->set(AdminKit::class, $adminKit);
        
    } catch (Exception $e) {
        // If AdminKit initialization fails, still return app for installation
        error_log('AdminKit initialization failed: ' . $e->getMessage());
    }
} else {
    // Setup installation routes - handle all methods and paths
    $app->any('/install[/{params:.*}]', function ($request, $response, $args) {
        if (file_exists(ADMINKIT_ROOT . '/install/index.php')) {
            ob_start();
            require ADMINKIT_ROOT . '/install/index.php';
            $content = ob_get_clean();
            $response->getBody()->write($content);
        } else {
            $response->getBody()->write('<h1>Installation Required</h1><p>Please create the installation files.</p>');
        }
        return $response;
    });
    
    $app->get('/', function ($request, $response) {
        return $response->withHeader('Location', '/install/')->withStatus(302);
    });
}

// Always add welcome route (works before and after installation)
$app->get('/welcome', function ($request, $response) {
    // Simple welcome page without dependencies for now
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to AdminKit</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: white; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); max-width: 500px; }
        h1 { color: #374151; margin-bottom: 1rem; font-size: 2.5rem; }
        p { color: #6b7280; margin-bottom: 2rem; line-height: 1.6; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🚀 AdminKit</h1>
        <p>A powerful, modern admin panel toolkit for PHP applications</p>
        <a href="/admin" class="btn">Go to Admin Panel</a>
        <a href="/install" class="btn" style="background: #10b981; margin-left: 1rem;">Installation</a>
    </div>
</body>
</html>';
    
    $response->getBody()->write($html);
    return $response;
});

return $app;
