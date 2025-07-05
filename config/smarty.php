<?php

declare(strict_types=1);

/**
 * AdminKit - Smarty Template Engine Configuration
 * 
 * This file provides Smarty template engine configuration for AdminKit.
 */

use Smarty;

return function(): Smarty {
    $smarty = new Smarty();
    
    // Set directories
    $smarty->setTemplateDir(ADMINKIT_ROOT . '/templates');
    $smarty->setCompileDir(ADMINKIT_ROOT . '/var/cache/smarty/compile');
    $smarty->setCacheDir(ADMINKIT_ROOT . '/var/cache/smarty/cache');
    $smarty->setConfigDir(ADMINKIT_ROOT . '/config');
    
    // Create directories if they don't exist
    $dirs = [
        ADMINKIT_ROOT . '/var',
        ADMINKIT_ROOT . '/var/cache',
        ADMINKIT_ROOT . '/var/cache/smarty',
        ADMINKIT_ROOT . '/var/cache/smarty/compile',
        ADMINKIT_ROOT . '/var/cache/smarty/cache'
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Basic configuration
    $smarty->debugging = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    $smarty->caching = ($_ENV['SMARTY_CACHING'] ?? 'false') === 'true';
    $smarty->cache_lifetime = (int)($_ENV['SMARTY_CACHE_LIFETIME'] ?? 3600);
    $smarty->force_compile = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    $smarty->compile_check = true;
    
    // Security settings
    $smarty->php_handling = Smarty::PHP_REMOVE;
    $smarty->security_policy = null; // Can be set later if needed
    
    // Error reporting
    $smarty->error_reporting = ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? E_ALL : 0;
    
    // Assign global variables
    $smarty->assign('app_name', $_ENV['APP_NAME'] ?? 'AdminKit');
    $smarty->assign('app_url', $_ENV['APP_URL'] ?? 'http://localhost');
    $smarty->assign('app_debug', ($_ENV['APP_DEBUG'] ?? 'false') === 'true');
    $smarty->assign('brand_name', $_ENV['ADMINKIT_BRAND_NAME'] ?? 'AdminKit');
    $smarty->assign('route_prefix', $_ENV['ADMINKIT_ROUTE_PREFIX'] ?? '/admin');
    $smarty->assign('locale', $_ENV['APP_LOCALE'] ?? 'en');
    $smarty->assign('timezone', $_ENV['APP_TIMEZONE'] ?? 'UTC');
    
    // Register custom functions
    $smarty->registerPlugin('function', 'adminkit_translate', function($params, $smarty) {
        $key = $params['key'] ?? '';
        $default = $params['default'] ?? $key;
        
        // Simple translation function - in real implementation, this would use LocalizationService
        $translations = [
            'welcome_to_adminkit' => 'Welcome to AdminKit',
            'dashboard' => 'Dashboard',
            'login' => 'Login',
            'logout' => 'Logout',
            'users' => 'Users',
            'settings' => 'Settings',
            'administration' => 'Administration',
        ];
        
        return $translations[$key] ?? $default;
    });
    
    $smarty->registerPlugin('function', 'adminkit_asset', function($params, $smarty) {
        $path = $params['path'] ?? '';
        $version = $params['version'] ?? '1.0.7';
        
        return '/assets/' . ltrim($path, '/') . '?v=' . $version;
    });
    
    $smarty->registerPlugin('function', 'adminkit_route', function($params, $smarty) {
        $name = $params['name'] ?? '';
        $prefix = $_ENV['ADMINKIT_ROUTE_PREFIX'] ?? '/admin';
        
        $routes = [
            'dashboard' => $prefix,
            'login' => $prefix . '/login',
            'logout' => $prefix . '/logout',
            'users' => $prefix . '/users',
        ];
        
        return $routes[$name] ?? $prefix;
    });
    
    // Register modifiers
    $smarty->registerPlugin('modifier', 'adminkit_format_date', function($date, $format = 'Y-m-d H:i:s') {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        if ($date instanceof DateTime) {
            return $date->format($format);
        }
        
        return $date;
    });
    
    $smarty->registerPlugin('modifier', 'adminkit_truncate', function($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    });
    
    // Set delimiters (optional - use default {})
    $smarty->left_delimiter = '{';
    $smarty->right_delimiter = '}';
    
    return $smarty;
};
