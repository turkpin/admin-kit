<?php

declare(strict_types=1);

/**
 * AdminKit - Container Configuration
 * 
 * This file defines the dependency injection container bindings for AdminKit.
 */

use DI\Container;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;
use Smarty;
use AdminKit\Services\ConfigService;
use AdminKit\Services\SmartyService;
use AdminKit\Services\AuthService;
use AdminKit\Services\CacheService;
use AdminKit\Services\LocalizationService;

return [
    // Configuration Service
    ConfigService::class => function (ContainerInterface $container) {
        return new ConfigService();
    },

    // Cache Service
    CacheService::class => function (ContainerInterface $container) {
        $config = [
            'enabled' => ($_ENV['CACHE_ENABLED'] ?? 'true') === 'true',
            'prefix' => $_ENV['CACHE_PREFIX'] ?? 'adminkit',
            'cache_dir' => ADMINKIT_ROOT . '/var/cache',
        ];
        return new CacheService(null, $config['enabled'], $config['prefix']);
    },

    // Localization Service
    LocalizationService::class => function (ContainerInterface $container) {
        $translationsPath = ADMINKIT_ROOT . '/src/Translations';
        $service = new LocalizationService($translationsPath);
        $service->setLocale($_ENV['APP_LOCALE'] ?? 'en');
        return $service;
    },

    // Smarty Template Engine
    Smarty::class => function (ContainerInterface $container) {
        $smarty = new Smarty();
        
        // Set directories
        $smarty->setTemplateDir(ADMINKIT_ROOT . '/templates');
        $smarty->setCompileDir(ADMINKIT_ROOT . '/var/cache/smarty/compile');
        $smarty->setCacheDir(ADMINKIT_ROOT . '/var/cache/smarty/cache');
        $smarty->setConfigDir(ADMINKIT_ROOT . '/config');
        
        // Create directories if they don't exist
        $dirs = [
            ADMINKIT_ROOT . '/var/cache/smarty/compile',
            ADMINKIT_ROOT . '/var/cache/smarty/cache'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Configuration
        $smarty->debugging = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        $smarty->caching = ($_ENV['SMARTY_CACHING'] ?? 'false') === 'true';
        $smarty->cache_lifetime = (int)($_ENV['SMARTY_CACHE_LIFETIME'] ?? 3600);
        
        // Assign global variables
        $smarty->assign('app_name', $_ENV['APP_NAME'] ?? 'AdminKit');
        $smarty->assign('app_url', $_ENV['APP_URL'] ?? 'http://localhost');
        $smarty->assign('brand_name', $_ENV['ADMINKIT_BRAND_NAME'] ?? 'AdminKit');
        $smarty->assign('route_prefix', $_ENV['ADMINKIT_ROUTE_PREFIX'] ?? '/admin');
        
        return $smarty;
    },

    // Smarty Service
    SmartyService::class => function (ContainerInterface $container) {
        $smarty = $container->get(Smarty::class);
        $config = [
            'template_path' => ADMINKIT_ROOT . '/templates',
            'cache_enabled' => ($_ENV['SMARTY_CACHING'] ?? 'false') === 'true',
        ];
        return new SmartyService($smarty, $config);
    },

    // Doctrine Entity Manager
    EntityManager::class => function (ContainerInterface $container) {
        // Database configuration
        $connectionParams = [
            'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['DB_PORT'] ?? 3306),
            'dbname' => $_ENV['DB_DATABASE'] ?? 'adminkit',
            'user' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ];

        // Entity paths
        $entityPaths = [ADMINKIT_ROOT . '/src/Entities'];

        // Doctrine configuration
        $isDevMode = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        $config = ORMSetup::createAttributeMetadataConfiguration(
            $entityPaths,
            $isDevMode,
            ADMINKIT_ROOT . '/var/cache/doctrine',
            null
        );

        // Create connection and entity manager
        $connection = DriverManager::getConnection($connectionParams, $config);
        return new EntityManager($connection, $config);
    },

    // Auth Service
    AuthService::class => function (ContainerInterface $container) {
        $entityManager = $container->get(EntityManager::class);
        $config = [
            'session_name' => $_ENV['SESSION_NAME'] ?? 'adminkit_session',
            'session_timeout' => (int)($_ENV['SESSION_TIMEOUT'] ?? 7200),
            'password_min_length' => (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 8),
            'max_login_attempts' => (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5),
            'lockout_duration' => (int)($_ENV['LOCKOUT_DURATION'] ?? 900),
        ];
        return new AuthService($entityManager, $config);
    },
];
