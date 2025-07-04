<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Providers;

use Psr\Container\ContainerInterface;
use Turkpin\AdminKit\AdminKit;
use Turkpin\AdminKit\Services\{
    AuthService,
    CacheService,
    ConfigService,
    LocalizationService,
    QueueService,
    PerformanceService,
    NotificationService,
    WebSocketService,
    AssetService,
    DynamicFormService,
    BreadcrumbService,
    FilterService,
    TwoFactorService,
    PluginService,
    AuditService,
    BatchOperationService,
    ChartService,
    ExportService,
    ImportService,
    SearchService,
    SmartyService,
    ThemeService,
    ValidationService
};

/**
 * AdminKit Service Provider
 * 
 * Registers all AdminKit services in the container and provides
 * automatic service discovery and configuration.
 */
class AdminKitServiceProvider
{
    private ContainerInterface $container;
    private array $config;

    public function __construct(ContainerInterface $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * Register all AdminKit services
     */
    public function register(): void
    {
        $this->registerCoreServices();
        $this->registerEnterpriseServices();
        $this->registerUIServices();
        $this->registerUtilityServices();
    }

    /**
     * Boot AdminKit services after registration
     */
    public function boot(): void
    {
        $this->loadConfiguration();
        $this->publishAssets();
        $this->registerMiddleware();
        $this->loadTranslations();
    }

    /**
     * Register core services
     */
    private function registerCoreServices(): void
    {
        // Configuration service (must be first)
        $this->container->set(ConfigService::class, function () {
            return new ConfigService($this->config);
        });

        // Authentication service
        $this->container->set(AuthService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new AuthService($config->get('auth', []));
        });

        // Cache service
        $this->container->set(CacheService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new CacheService($config->get('cache', []));
        });

        // Localization service
        $this->container->set(LocalizationService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new LocalizationService($config->get('locale', 'tr'));
        });

        // Validation service
        $this->container->set(ValidationService::class, function () {
            return new ValidationService();
        });
    }

    /**
     * Register enterprise services
     */
    private function registerEnterpriseServices(): void
    {
        // Two-Factor Authentication
        $this->container->set(TwoFactorService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new TwoFactorService($config->get('auth.2fa_enabled', false));
        });

        // Queue service
        $this->container->set(QueueService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new QueueService($config->get('queue', []));
        });

        // Performance monitoring
        $this->container->set(PerformanceService::class, function () {
            $config = $this->container->get(ConfigService::class);
            $cache = $this->container->get(CacheService::class);
            return new PerformanceService($config->get('performance', []), $cache);
        });

        // Notification service
        $this->container->set(NotificationService::class, function () {
            $config = $this->container->get(ConfigService::class);
            $queue = $this->container->get(QueueService::class);
            return new NotificationService($config->get('notifications', []), $queue);
        });

        // WebSocket service
        $this->container->set(WebSocketService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new WebSocketService($config->get('websocket', []));
        });

        // Plugin service
        $this->container->set(PluginService::class, function () {
            return new PluginService();
        });

        // Audit service
        $this->container->set(AuditService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new AuditService($config->get('audit', []));
        });
    }

    /**
     * Register UI and UX services
     */
    private function registerUIServices(): void
    {
        // Asset management
        $this->container->set(AssetService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new AssetService($config->get('assets', []));
        });

        // Dynamic forms
        $this->container->set(DynamicFormService::class, function () {
            $validation = $this->container->get(ValidationService::class);
            return new DynamicFormService($validation);
        });

        // Breadcrumb navigation
        $this->container->set(BreadcrumbService::class, function () {
            $localization = $this->container->get(LocalizationService::class);
            return new BreadcrumbService($localization);
        });

        // Theme service
        $this->container->set(ThemeService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new ThemeService($config->get('theme', []));
        });

        // Smarty template service
        $this->container->set(SmartyService::class, function () {
            $config = $this->container->get(ConfigService::class);
            return new SmartyService($config->get('templates', []));
        });
    }

    /**
     * Register utility services
     */
    private function registerUtilityServices(): void
    {
        // Filter service
        $this->container->set(FilterService::class, function () {
            return new FilterService();
        });

        // Search service
        $this->container->set(SearchService::class, function () {
            $cache = $this->container->get(CacheService::class);
            return new SearchService($cache);
        });

        // Chart service
        $this->container->set(ChartService::class, function () {
            return new ChartService();
        });

        // Export service
        $this->container->set(ExportService::class, function () {
            $queue = $this->container->get(QueueService::class);
            return new ExportService($queue);
        });

        // Import service
        $this->container->set(ImportService::class, function () {
            $validation = $this->container->get(ValidationService::class);
            $queue = $this->container->get(QueueService::class);
            return new ImportService($validation, $queue);
        });

        // Batch operations
        $this->container->set(BatchOperationService::class, function () {
            $queue = $this->container->get(QueueService::class);
            $audit = $this->container->get(AuditService::class);
            return new BatchOperationService($queue, $audit);
        });
    }

    /**
     * Load configuration files
     */
    private function loadConfiguration(): void
    {
        $configService = $this->container->get(ConfigService::class);
        
        // Load default configuration
        $defaultConfigPath = adminkit_path('config/default.php');
        if (file_exists($defaultConfigPath)) {
            $defaultConfig = require $defaultConfigPath;
            $configService->merge($defaultConfig);
        }

        // Load environment-specific configuration
        $env = adminkit_env('ENV', 'production');
        $envConfigPath = adminkit_path("config/{$env}.php");
        if (file_exists($envConfigPath)) {
            $envConfig = require $envConfigPath;
            $configService->merge($envConfig);
        }
    }

    /**
     * Publish assets to public directory
     */
    private function publishAssets(): void
    {
        $assetService = $this->container->get(AssetService::class);
        
        // Auto-publish assets in development
        if (adminkit_env('DEBUG', false)) {
            $assetService->publishToPublic();
        }
    }

    /**
     * Register middleware
     */
    private function registerMiddleware(): void
    {
        // Register authentication middleware
        $authService = $this->container->get(AuthService::class);
        $authService->registerMiddleware();

        // Register performance monitoring middleware
        $performanceService = $this->container->get(PerformanceService::class);
        $performanceService->registerMiddleware();
    }

    /**
     * Load translations
     */
    private function loadTranslations(): void
    {
        $localizationService = $this->container->get(LocalizationService::class);
        
        // Load package translations
        $translationPath = adminkit_path('src/Translations');
        $localizationService->loadFromDirectory($translationPath);
        
        // Load user translations (if exists)
        $userTranslationPath = getcwd() . '/translations';
        if (is_dir($userTranslationPath)) {
            $localizationService->loadFromDirectory($userTranslationPath);
        }
    }

    /**
     * Get all registered services
     */
    public function getServices(): array
    {
        return [
            // Core services
            'config' => ConfigService::class,
            'auth' => AuthService::class,
            'cache' => CacheService::class,
            'localization' => LocalizationService::class,
            'validation' => ValidationService::class,
            
            // Enterprise services
            'twoFactor' => TwoFactorService::class,
            'queue' => QueueService::class,
            'performance' => PerformanceService::class,
            'notification' => NotificationService::class,
            'websocket' => WebSocketService::class,
            'plugin' => PluginService::class,
            'audit' => AuditService::class,
            
            // UI services
            'asset' => AssetService::class,
            'dynamicForm' => DynamicFormService::class,
            'breadcrumb' => BreadcrumbService::class,
            'theme' => ThemeService::class,
            'smarty' => SmartyService::class,
            
            // Utility services
            'filter' => FilterService::class,
            'search' => SearchService::class,
            'chart' => ChartService::class,
            'export' => ExportService::class,
            'import' => ImportService::class,
            'batch' => BatchOperationService::class,
        ];
    }

    /**
     * Check if service is registered
     */
    public function hasService(string $serviceName): bool
    {
        $services = $this->getServices();
        return isset($services[$serviceName]) && $this->container->has($services[$serviceName]);
    }

    /**
     * Get service instance
     */
    public function getService(string $serviceName)
    {
        $services = $this->getServices();
        
        if (!isset($services[$serviceName])) {
            throw new \InvalidArgumentException("Service '{$serviceName}' not found");
        }
        
        return $this->container->get($services[$serviceName]);
    }

    /**
     * Get AdminKit version
     */
    public function getVersion(): string
    {
        return adminkit_version();
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Create AdminKit instance with all services
     */
    public function createAdminKit(): AdminKit
    {
        $configService = $this->container->get(ConfigService::class);
        return new AdminKit($configService->all(), $this->container);
    }
}
