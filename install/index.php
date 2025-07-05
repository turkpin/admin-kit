<?php

declare(strict_types=1);

/**
 * AdminKit Installation Wizard
 * 
 * This file provides a web-based installation interface for AdminKit.
 */

// Define paths
define('ADMINKIT_ROOT', dirname(__DIR__));
define('INSTALL_ROOT', __DIR__);

// Load Composer autoloader if available
if (file_exists(ADMINKIT_ROOT . '/vendor/autoload.php')) {
    require_once ADMINKIT_ROOT . '/vendor/autoload.php';
}

// Start session
session_start();

// Installation steps
$steps = [
    'welcome' => 'Welcome',
    'requirements' => 'System Requirements',
    'database' => 'Database Configuration',
    'admin' => 'Admin User Setup',
    'complete' => 'Installation Complete'
];

// Current step
$currentStep = $_GET['step'] ?? 'welcome';
if (!array_key_exists($currentStep, $steps)) {
    $currentStep = 'welcome';
}

// Initialize error variables
$error = null;
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 'database':
            handleDatabaseSetup();
            break;
        case 'admin':
            handleAdminSetup();
            break;
    }
}

/**
 * Handle database configuration
 */
function handleDatabaseSetup() {
    $data = [
        'DB_HOST' => $_POST['db_host'] ?? 'localhost',
        'DB_PORT' => $_POST['db_port'] ?? '3306',
        'DB_DATABASE' => $_POST['db_database'] ?? 'adminkit',
        'DB_USERNAME' => $_POST['db_username'] ?? 'root',
        'DB_PASSWORD' => $_POST['db_password'] ?? '',
        'DB_CHARSET' => 'utf8mb4',
    ];
    
    // Test database connection
    try {
        $dsn = "mysql:host={$data['DB_HOST']};port={$data['DB_PORT']};charset={$data['DB_CHARSET']}";
        $pdo = new PDO($dsn, $data['DB_USERNAME'], $data['DB_PASSWORD']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['DB_DATABASE']}` CHARACTER SET {$data['DB_CHARSET']} COLLATE {$data['DB_CHARSET']}_unicode_ci");
        
        // Store database config in session
        $_SESSION['db_config'] = $data;
        
        // Redirect to admin setup
        header('Location: ?step=admin');
        exit;
        
    } catch (PDOException $e) {
        $error = 'Database connection failed: ' . $e->getMessage();
    }
}

/**
 * Handle admin user setup
 */
function handleAdminSetup() {
    $adminData = [
        'name' => $_POST['admin_name'] ?? '',
        'email' => $_POST['admin_email'] ?? '',
        'password' => $_POST['admin_password'] ?? '',
        'password_confirm' => $_POST['admin_password_confirm'] ?? '',
    ];
    
    // Validate input
    $errors = [];
    if (empty($adminData['name'])) $errors[] = 'Name is required';
    if (empty($adminData['email'])) $errors[] = 'Email is required';
    if (!filter_var($adminData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($adminData['password'])) $errors[] = 'Password is required';
    if (strlen($adminData['password']) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($adminData['password'] !== $adminData['password_confirm']) $errors[] = 'Passwords do not match';
    
    if (empty($errors)) {
        // Create .env file
        createEnvFile($_SESSION['db_config'], $adminData);
        
        // Run migrations and create admin user
        if (createAdminUser($adminData)) {
            // Redirect to completion
            header('Location: ?step=complete');
            exit;
        } else {
            $error = 'Failed to create admin user';
        }
    }
}

/**
 * Create .env file
 */
function createEnvFile(array $dbConfig, array $adminData) {
    $envContent = <<<ENV
# AdminKit Configuration
APP_NAME=AdminKit
APP_URL=http://localhost
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_LOCALE=en

# Database Configuration
DB_DRIVER=pdo_mysql
DB_HOST={$dbConfig['DB_HOST']}
DB_PORT={$dbConfig['DB_PORT']}
DB_DATABASE={$dbConfig['DB_DATABASE']}
DB_USERNAME={$dbConfig['DB_USERNAME']}
DB_PASSWORD={$dbConfig['DB_PASSWORD']}
DB_CHARSET={$dbConfig['DB_CHARSET']}

# Cache Configuration
CACHE_ENABLED=true
CACHE_PREFIX=adminkit

# Session Configuration
SESSION_NAME=adminkit_session
SESSION_TIMEOUT=7200

# Security Configuration
PASSWORD_MIN_LENGTH=8
MAX_LOGIN_ATTEMPTS=5
LOCKOUT_DURATION=900

# AdminKit Configuration
ADMINKIT_ROUTE_PREFIX=/admin
ADMINKIT_BRAND_NAME=AdminKit

# Smarty Configuration
SMARTY_CACHING=false
SMARTY_CACHE_LIFETIME=3600
ENV;

    file_put_contents(ADMINKIT_ROOT . '/.env', $envContent);
}

/**
 * Create admin user
 */
function createAdminUser(array $adminData): bool {
    try {
        // Run CLI command to create admin user
        $command = 'php ' . ADMINKIT_ROOT . '/bin/adminkit user:create --admin ' . 
                   escapeshellarg($adminData['email']) . ' ' . 
                   escapeshellarg($adminData['password']) . ' ' .
                   escapeshellarg($adminData['name']);
        
        exec($command, $output, $returnCode);
        
        return $returnCode === 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check system requirements
 */
function checkRequirements(): array {
    $requirements = [
        'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'JSON Extension' => extension_loaded('json'),
        'Mbstring Extension' => extension_loaded('mbstring'),
        'Zip Extension' => extension_loaded('zip'),
        'Writable var/ directory' => is_writable(ADMINKIT_ROOT . '/var') || mkdir(ADMINKIT_ROOT . '/var', 0755, true),
        'Writable config/ directory' => is_writable(dirname(ADMINKIT_ROOT . '/config/')) || mkdir(ADMINKIT_ROOT . '/config', 0755, true),
        'Composer Dependencies' => file_exists(ADMINKIT_ROOT . '/vendor/autoload.php'),
    ];
    
    return $requirements;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminKit Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #374151; line-height: 1.6; }
        .container { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; text-align: center; }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .content { padding: 2rem; }
        .steps { display: flex; justify-content: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .step { padding: 0.5rem 1rem; background: #e5e7eb; border-radius: 6px; font-size: 0.875rem; }
        .step.active { background: #3b82f6; color: white; }
        .step.completed { background: #10b981; color: white; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; font-size: 1rem; transition: background 0.2s; }
        .btn:hover { background: #2563eb; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
        .requirements { list-style: none; }
        .requirements li { padding: 0.5rem 0; display: flex; align-items: center; }
        .requirements .pass::before { content: '✓'; color: #16a34a; font-weight: bold; margin-right: 0.5rem; }
        .requirements .fail::before { content: '✗'; color: #dc2626; font-weight: bold; margin-right: 0.5rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } .steps { justify-content: flex-start; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>AdminKit Installation</h1>
                <p>Set up your admin panel in a few simple steps</p>
            </div>
            
            <div class="content">
                <!-- Progress Steps -->
                <div class="steps">
                    <?php foreach ($steps as $stepKey => $stepName): ?>
                        <div class="step <?= $stepKey === $currentStep ? 'active' : '' ?> <?= array_search($stepKey, array_keys($steps)) < array_search($currentStep, array_keys($steps)) ? 'completed' : '' ?>">
                            <?= $stepName ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Step Content -->
                <?php include INSTALL_ROOT . "/steps/{$currentStep}.php"; ?>
            </div>
        </div>
    </div>
</body>
</html>
