<?php

declare(strict_types=1);

/**
 * AdminKit - Doctrine ORM Configuration
 * 
 * This file provides Doctrine ORM configuration for AdminKit.
 */

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;

return function(): EntityManager {
    // Database configuration from environment
    $connectionParams = [
        'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['DB_PORT'] ?? 3306),
        'dbname' => $_ENV['DB_DATABASE'] ?? 'adminkit',
        'user' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        'driverOptions' => [
            1002 => 'SET NAMES utf8mb4'
        ]
    ];

    // Entity paths
    $entityPaths = [
        ADMINKIT_ROOT . '/src/Entities'
    ];

    // Doctrine configuration
    $isDevMode = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    
    // Setup configuration with attributes
    $config = ORMSetup::createAttributeMetadataConfiguration(
        $entityPaths,
        $isDevMode,
        ADMINKIT_ROOT . '/var/cache/doctrine',
        null
    );

    // Configure naming strategy
    $config->setNamingStrategy(new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy());

    // Configure quote strategy
    $config->setQuoteStrategy(new \Doctrine\ORM\Mapping\DefaultQuoteStrategy());

    // Configure proxy settings
    $config->setProxyDir(ADMINKIT_ROOT . '/var/cache/doctrine/proxies');
    $config->setProxyNamespace('AdminKit\Proxies');
    $config->setAutoGenerateProxyClasses($isDevMode);

    // Configure cache drivers
    if (!$isDevMode) {
        // Production cache configuration
        if (extension_loaded('apcu')) {
            $cache = new \Doctrine\Common\Cache\ApcuCache();
        } elseif (extension_loaded('memcached')) {
            $memcached = new \Memcached();
            $memcached->addServer('127.0.0.1', 11211);
            $cache = new \Doctrine\Common\Cache\MemcachedCache();
            $cache->setMemcached($memcached);
        } else {
            $cache = new \Doctrine\Common\Cache\ArrayCache();
        }
        
        $config->setMetadataCache($cache);
        $config->setQueryCache($cache);
        $config->setResultCache($cache);
    }

    // SQL Logger for development
    if ($isDevMode) {
        $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
    }

    // Custom types
    if (!\Doctrine\DBAL\Types\Type::hasType('json')) {
        \Doctrine\DBAL\Types\Type::addType('json', \Doctrine\DBAL\Types\JsonType::class);
    }

    // Create connection and entity manager
    try {
        $connection = DriverManager::getConnection($connectionParams, $config);
        $entityManager = new EntityManager($connection, $config);
        
        return $entityManager;
    } catch (\Exception $e) {
        throw new \RuntimeException('Failed to create Doctrine EntityManager: ' . $e->getMessage(), 0, $e);
    }
};
