<?php

declare(strict_types=1);

namespace AdminKit;

use Slim\App;
use Smarty;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use AdminKit\Services\ConfigService;
use AdminKit\Services\SmartyService;
use AdminKit\Services\AuthService;
use AdminKit\Controllers\AuthController;
use AdminKit\Controllers\DashboardController;
use AdminKit\Controllers\CrudController;
use AdminKit\Middleware\AuthMiddleware;
use AdminKit\Middleware\RoleMiddleware;

class AdminKit
{
    private App $app;
    private ContainerInterface $container;
    private array $config;
    private array $entities = [];
    private array $widgets = [];

    public function __construct(App $app, array $config = [])
    {
        $this->app = $app;
        $this->container = $app->getContainer();
        $this->config = array_merge($this->getDefaultConfig(), $config);
        
        $this->initializeServices();
        $this->setupMiddleware();
        $this->setupRoutes();
    }

    private function getDefaultConfig(): array
    {
        return [
            'route_prefix' => '/admin',
            'template_path' => __DIR__ . '/../templates',
            'assets_path' => '/assets/admin',
            'auth_required' => true,
            'rbac_enabled' => true,
            'pagination_limit' => 20,
            'upload_path' => 'uploads/',
            'theme' => 'default',
            'brand_name' => 'AdminKit',
            'dashboard_title' => 'Dashboard',
            'date_format' => 'Y-m-d H:i:s',
            'locale' => 'tr',
            'csrf_protection' => true,
        ];
    }

    private function initializeServices(): void
    {
        // Services are managed by the container
        // We'll get them when needed
    }

    private function setupMiddleware(): void
    {
        if ($this->config['auth_required']) {
            $this->app->add(new AuthMiddleware($this->getAuthService()));
        }

        if ($this->config['rbac_enabled']) {
            $this->app->add(new RoleMiddleware($this->getAuthService()));
        }
    }

    private function setupRoutes(): void
    {
        $prefix = $this->config['route_prefix'];
        
        // Auth routes
        $this->app->get($prefix . '/login', [AuthController::class, 'loginForm']);
        $this->app->post($prefix . '/login', [AuthController::class, 'login']);
        $this->app->get($prefix . '/logout', [AuthController::class, 'logout']);
        
        // Dashboard route
        $this->app->get($prefix, [DashboardController::class, 'index']);
        $this->app->get($prefix . '/', [DashboardController::class, 'index']);
        
        // CRUD routes will be added dynamically
    }

    public function addEntity(string $entityClass, array $config = []): self
    {
        $entityName = $this->getEntityName($entityClass);
        
        $defaultConfig = [
            'title' => $entityName,
            'fields' => [],
            'actions' => ['index', 'show', 'new', 'edit', 'delete'],
            'filters' => [],
            'permissions' => [],
            'pagination' => $this->config['pagination_limit'],
            'searchable' => true,
            'sortable' => true,
        ];

        $this->entities[$entityName] = array_merge($defaultConfig, $config);
        $this->entities[$entityName]['class'] = $entityClass;
        
        $this->setupEntityRoutes($entityName);
        
        return $this;
    }

    public function addDashboardWidget(string $name, array $config): self
    {
        $defaultConfig = [
            'title' => ucfirst($name),
            'value' => 0,
            'icon' => 'chart-bar',
            'color' => 'blue',
            'type' => 'stat',
        ];

        $this->widgets[$name] = array_merge($defaultConfig, $config);
        
        return $this;
    }

    public function addCustomRoute(string $method, string $path, callable $handler): self
    {
        $fullPath = $this->config['route_prefix'] . $path;
        $this->app->map([$method], $fullPath, $handler);
        
        return $this;
    }

    private function setupEntityRoutes(string $entityName): void
    {
        $prefix = $this->config['route_prefix'];
        $entityConfig = $this->entities[$entityName];
        
        if (in_array('index', $entityConfig['actions'])) {
            $this->app->get($prefix . '/' . $entityName, [CrudController::class, 'index'])
                ->setArgument('entity', $entityName);
        }
        
        if (in_array('show', $entityConfig['actions'])) {
            $this->app->get($prefix . '/' . $entityName . '/{id}', [CrudController::class, 'show'])
                ->setArgument('entity', $entityName);
        }
        
        if (in_array('new', $entityConfig['actions'])) {
            $this->app->get($prefix . '/' . $entityName . '/new', [CrudController::class, 'new'])
                ->setArgument('entity', $entityName);
            $this->app->post($prefix . '/' . $entityName . '/new', [CrudController::class, 'create'])
                ->setArgument('entity', $entityName);
        }
        
        if (in_array('edit', $entityConfig['actions'])) {
            $this->app->get($prefix . '/' . $entityName . '/{id}/edit', [CrudController::class, 'edit'])
                ->setArgument('entity', $entityName);
            $this->app->post($prefix . '/' . $entityName . '/{id}/edit', [CrudController::class, 'update'])
                ->setArgument('entity', $entityName);
        }
        
        if (in_array('delete', $entityConfig['actions'])) {
            $this->app->delete($prefix . '/' . $entityName . '/{id}', [CrudController::class, 'delete'])
                ->setArgument('entity', $entityName);
        }
    }

    private function getEntityName(string $entityClass): string
    {
        $parts = explode('\\', $entityClass);
        return strtolower(end($parts));
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getWidgets(): array
    {
        return $this->widgets;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get(EntityManagerInterface::class);
    }

    public function getSmarty(): Smarty
    {
        return $this->container->get(Smarty::class);
    }

    public function getAuthService(): AuthService
    {
        return $this->container->get(AuthService::class);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getApp(): App
    {
        return $this->app;
    }
}
