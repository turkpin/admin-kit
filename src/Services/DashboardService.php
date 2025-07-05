<?php

declare(strict_types=1);

namespace AdminKit\Services;

use AdminKit\AdminKit;

class DashboardService
{
    private AdminKit $adminKit;
    private array $widgets = [];
    private array $menuItems = [];
    private array $breadcrumbs = [];

    public function __construct(AdminKit $adminKit)
    {
        $this->adminKit = $adminKit;
    }

    /**
     * Enhanced widget management
     */
    public function addWidget(string $name, array $config): self
    {
        $defaultConfig = [
            'title' => ucfirst($name),
            'type' => 'counter',
            'value' => 0,
            'icon' => 'chart-bar',
            'color' => 'blue',
            'position' => 'auto',
            'width' => 'auto',
            'height' => 'auto',
            'refresh_interval' => 0,
            'permissions' => [],
            'template' => null,
            'data' => null,
            'link' => null,
        ];

        $this->widgets[$name] = array_merge($defaultConfig, $config);
        return $this;
    }

    public function getWidgets(): array
    {
        $processedWidgets = [];
        
        foreach ($this->widgets as $name => $config) {
            // Check permissions
            if (!empty($config['permissions'])) {
                // In a real implementation, check user permissions here
                // For now, we'll assume all widgets are accessible
            }

            // Process widget value if it's a callable
            if (is_callable($config['value'])) {
                try {
                    $config['value'] = call_user_func($config['value']);
                } catch (\Exception $e) {
                    $config['value'] = 'Error';
                    $config['error'] = $e->getMessage();
                }
            }

            // Process widget data if it's a callable
            if (is_callable($config['data'])) {
                try {
                    $config['data'] = call_user_func($config['data']);
                } catch (\Exception $e) {
                    $config['data'] = [];
                    $config['error'] = $e->getMessage();
                }
            }

            $processedWidgets[$name] = $config;
        }

        return $processedWidgets;
    }

    /**
     * Navigation menu builder
     */
    public function addMenuItem(array $item): self
    {
        $defaultItem = [
            'label' => '',
            'route' => null,
            'url' => null,
            'icon' => null,
            'badge' => null,
            'children' => [],
            'permissions' => [],
            'active' => false,
            'order' => 0,
        ];

        $this->menuItems[] = array_merge($defaultItem, $item);
        return $this;
    }

    public function getMenuItems(): array
    {
        // Sort by order
        usort($this->menuItems, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return array_filter($this->menuItems, function($item) {
            // Check permissions
            if (!empty($item['permissions'])) {
                // In a real implementation, check user permissions here
                return true;
            }
            return true;
        });
    }

    public function buildDefaultMenu(): self
    {
        $this->addMenuItem([
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'home',
            'order' => 1,
        ]);

        // Add entity menu items from AdminKit
        $entities = $this->adminKit->getEntities();
        $order = 10;
        
        foreach ($entities as $entityName => $config) {
            $this->addMenuItem([
                'label' => $config['title'] ?? ucfirst($entityName),
                'route' => $entityName . '_index',
                'icon' => $config['icon'] ?? 'table',
                'order' => $order++,
            ]);
        }

        $this->addMenuItem([
            'label' => 'Settings',
            'icon' => 'settings',
            'children' => [
                [
                    'label' => 'Users',
                    'route' => 'users_index',
                    'icon' => 'users',
                ],
                [
                    'label' => 'System',
                    'route' => 'settings_system',
                    'icon' => 'cog',
                ],
            ],
            'order' => 100,
        ]);

        return $this;
    }

    /**
     * Breadcrumb system
     */
    public function setBreadcrumbs(array $breadcrumbs): self
    {
        $this->breadcrumbs = [];
        
        foreach ($breadcrumbs as $breadcrumb) {
            $this->addBreadcrumb($breadcrumb);
        }
        
        return $this;
    }

    public function addBreadcrumb(array $breadcrumb): self
    {
        $defaultBreadcrumb = [
            'label' => '',
            'url' => null,
            'active' => false,
        ];

        $this->breadcrumbs[] = array_merge($defaultBreadcrumb, $breadcrumb);
        return $this;
    }

    public function getBreadcrumbs(): array
    {
        // Mark last breadcrumb as active
        if (!empty($this->breadcrumbs)) {
            $lastIndex = count($this->breadcrumbs) - 1;
            $this->breadcrumbs[$lastIndex]['active'] = true;
        }

        return $this->breadcrumbs;
    }

    public function generateBreadcrumbsFromRoute(string $route, array $params = []): self
    {
        $this->breadcrumbs = [];
        
        // Add dashboard as first breadcrumb
        $this->addBreadcrumb([
            'label' => 'Dashboard',
            'url' => '/admin',
        ]);

        // Parse route and generate breadcrumbs
        $routeParts = explode('_', $route);
        
        if (count($routeParts) >= 2) {
            $entityName = $routeParts[0];
            $action = $routeParts[1];
            
            $entities = $this->adminKit->getEntities();
            if (isset($entities[$entityName])) {
                $entityConfig = $entities[$entityName];
                
                // Add entity list breadcrumb
                $this->addBreadcrumb([
                    'label' => $entityConfig['title'] ?? ucfirst($entityName),
                    'url' => "/admin/{$entityName}",
                ]);
                
                // Add action-specific breadcrumb
                if ($action !== 'index') {
                    $actionLabels = [
                        'show' => 'View',
                        'new' => 'New',
                        'edit' => 'Edit',
                        'delete' => 'Delete',
                    ];
                    
                    $actionLabel = $actionLabels[$action] ?? ucfirst($action);
                    
                    $this->addBreadcrumb([
                        'label' => $actionLabel,
                        'url' => null, // No URL for current page
                    ]);
                }
            }
        }

        return $this;
    }

    /**
     * Dashboard statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total_widgets' => count($this->widgets),
            'total_menu_items' => count($this->menuItems),
            'total_entities' => count($this->adminKit->getEntities()),
        ];

        return $stats;
    }

    /**
     * Widget templates
     */
    public function getWidgetTemplate(string $type): string
    {
        $templates = [
            'counter' => 'widgets/counter.tpl',
            'chart' => 'widgets/chart.tpl',
            'list' => 'widgets/list.tpl',
            'table' => 'widgets/table.tpl',
            'progress' => 'widgets/progress.tpl',
            'custom' => 'widgets/custom.tpl',
        ];

        return $templates[$type] ?? 'widgets/default.tpl';
    }

    /**
     * Layout configuration
     */
    public function getLayoutConfig(): array
    {
        return [
            'sidebar_collapsed' => false,
            'theme' => 'default',
            'show_breadcrumbs' => true,
            'show_footer' => true,
            'fixed_header' => true,
            'fixed_sidebar' => true,
        ];
    }
}
