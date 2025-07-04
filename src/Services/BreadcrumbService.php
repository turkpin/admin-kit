<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;
use Turkpin\AdminKit\Services\LocalizationService;

class BreadcrumbService
{
    private CacheService $cacheService;
    private LocalizationService $localizationService;
    private array $config;
    private array $breadcrumbs;
    private array $routes;

    public function __construct(
        CacheService $cacheService,
        LocalizationService $localizationService,
        array $config = []
    ) {
        $this->cacheService = $cacheService;
        $this->localizationService = $localizationService;
        $this->config = array_merge([
            'cache_enabled' => true,
            'cache_ttl' => 3600,
            'separator' => ' / ',
            'home_label' => 'Dashboard',
            'home_url' => '/admin',
            'max_length' => 100,
            'auto_generate' => true,
            'show_icons' => true
        ], $config);
        
        $this->breadcrumbs = [];
        $this->routes = [];
        
        $this->registerDefaultRoutes();
    }

    /**
     * Register default route patterns
     */
    private function registerDefaultRoutes(): void
    {
        $this->routes = [
            // Dashboard
            '/admin' => [
                'label' => 'dashboard',
                'icon' => 'home',
                'parent' => null
            ],
            '/admin/dashboard' => [
                'label' => 'dashboard',
                'icon' => 'home',
                'parent' => null
            ],
            
            // User management
            '/admin/users' => [
                'label' => 'users',
                'icon' => 'users',
                'parent' => '/admin'
            ],
            '/admin/users/new' => [
                'label' => 'new_user',
                'icon' => 'user-plus',
                'parent' => '/admin/users'
            ],
            '/admin/users/{id}' => [
                'label' => 'user_detail',
                'icon' => 'user',
                'parent' => '/admin/users'
            ],
            '/admin/users/{id}/edit' => [
                'label' => 'edit_user',
                'icon' => 'edit',
                'parent' => '/admin/users/{id}'
            ],
            
            // Role management
            '/admin/roles' => [
                'label' => 'roles',
                'icon' => 'shield',
                'parent' => '/admin'
            ],
            '/admin/roles/new' => [
                'label' => 'new_role',
                'icon' => 'plus',
                'parent' => '/admin/roles'
            ],
            '/admin/roles/{id}/edit' => [
                'label' => 'edit_role',
                'icon' => 'edit',
                'parent' => '/admin/roles'
            ],
            
            // Settings
            '/admin/settings' => [
                'label' => 'settings',
                'icon' => 'cog',
                'parent' => '/admin'
            ],
            '/admin/settings/general' => [
                'label' => 'general_settings',
                'icon' => 'settings',
                'parent' => '/admin/settings'
            ],
            '/admin/settings/notifications' => [
                'label' => 'notification_settings',
                'icon' => 'bell',
                'parent' => '/admin/settings'
            ],
            
            // Reports
            '/admin/reports' => [
                'label' => 'reports',
                'icon' => 'chart-bar',
                'parent' => '/admin'
            ],
            '/admin/reports/performance' => [
                'label' => 'performance_report',
                'icon' => 'chart-line',
                'parent' => '/admin/reports'
            ],
            '/admin/reports/users' => [
                'label' => 'user_report',
                'icon' => 'chart-pie',
                'parent' => '/admin/reports'
            ]
        ];
    }

    /**
     * Add custom route pattern
     */
    public function addRoute(string $pattern, array $config): void
    {
        $this->routes[$pattern] = array_merge([
            'label' => 'unknown',
            'icon' => 'link',
            'parent' => null
        ], $config);
    }

    /**
     * Generate breadcrumbs for current path
     */
    public function generate(string $currentPath, array $context = []): array
    {
        if ($this->config['cache_enabled']) {
            $cacheKey = 'breadcrumbs:' . md5($currentPath . serialize($context));
            $cached = $this->cacheService->get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $breadcrumbs = $this->buildBreadcrumbs($currentPath, $context);
        
        if ($this->config['cache_enabled']) {
            $this->cacheService->set($cacheKey, $breadcrumbs, $this->config['cache_ttl']);
        }

        return $breadcrumbs;
    }

    /**
     * Build breadcrumb chain
     */
    private function buildBreadcrumbs(string $currentPath, array $context): array
    {
        $breadcrumbs = [];
        $route = $this->matchRoute($currentPath);
        
        if (!$route) {
            // Auto-generate if enabled
            if ($this->config['auto_generate']) {
                return $this->autoGenerateBreadcrumbs($currentPath, $context);
            }
            return $breadcrumbs;
        }

        // Build chain by following parent relationships
        $chain = $this->buildChain($currentPath, $route, $context);
        
        foreach ($chain as $item) {
            $breadcrumbs[] = [
                'label' => $this->getLabel($item['label'], $context),
                'url' => $item['url'],
                'icon' => $this->config['show_icons'] ? $item['icon'] : null,
                'active' => $item['url'] === $currentPath
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Match current path to route pattern
     */
    private function matchRoute(string $path): ?array
    {
        // Exact match first
        if (isset($this->routes[$path])) {
            return $this->routes[$path];
        }

        // Pattern matching with parameters
        foreach ($this->routes as $pattern => $config) {
            if ($this->matchPattern($pattern, $path)) {
                return $config;
            }
        }

        return null;
    }

    /**
     * Match URL pattern with parameters
     */
    private function matchPattern(string $pattern, string $path): bool
    {
        // Convert pattern to regex
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        return preg_match($regex, $path);
    }

    /**
     * Build breadcrumb chain by following parents
     */
    private function buildChain(string $currentPath, array $route, array $context): array
    {
        $chain = [];
        $current = [
            'label' => $route['label'],
            'url' => $currentPath,
            'icon' => $route['icon']
        ];
        
        // Add current item
        array_unshift($chain, $current);
        
        // Follow parent chain
        $parentPath = $route['parent'];
        while ($parentPath) {
            $parentRoute = $this->matchRoute($parentPath);
            if (!$parentRoute) {
                break;
            }
            
            // Replace parameters in parent path
            $resolvedParentPath = $this->resolvePathParameters($parentPath, $currentPath, $context);
            
            array_unshift($chain, [
                'label' => $parentRoute['label'],
                'url' => $resolvedParentPath,
                'icon' => $parentRoute['icon']
            ]);
            
            $parentPath = $parentRoute['parent'];
        }

        return $chain;
    }

    /**
     * Resolve path parameters from current path and context
     */
    private function resolvePathParameters(string $parentPath, string $currentPath, array $context): string
    {
        // Extract parameters from current path
        $params = $this->extractParameters($currentPath);
        
        // Replace parameters in parent path
        $resolved = $parentPath;
        foreach ($params as $key => $value) {
            $resolved = str_replace($key, $value, $resolved);
        }
        
        return $resolved;
    }

    /**
     * Extract parameters from path
     */
    private function extractParameters(string $path): array
    {
        $params = [];
        
        // Find matching pattern
        foreach ($this->routes as $pattern => $config) {
            if ($this->matchPattern($pattern, $path)) {
                preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
                $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
                $regex = '#^' . $regex . '$#';
                
                if (preg_match($regex, $path, $matches)) {
                    array_shift($matches); // Remove full match
                    
                    foreach ($paramNames[1] as $index => $paramName) {
                        if (isset($matches[$index])) {
                            $params['{' . $paramName . '}'] = $matches[$index];
                        }
                    }
                }
                break;
            }
        }
        
        return $params;
    }

    /**
     * Auto-generate breadcrumbs from URL structure
     */
    private function autoGenerateBreadcrumbs(string $path, array $context): array
    {
        $breadcrumbs = [];
        $segments = array_filter(explode('/', $path));
        $currentPath = '';
        
        foreach ($segments as $index => $segment) {
            $currentPath .= '/' . $segment;
            
            // Skip first segment if it's 'admin'
            if ($index === 0 && $segment === 'admin') {
                $breadcrumbs[] = [
                    'label' => $this->getLabel('dashboard', $context),
                    'url' => '/admin',
                    'icon' => $this->config['show_icons'] ? 'home' : null,
                    'active' => $currentPath === $path
                ];
                continue;
            }
            
            // Generate label from segment
            $label = $this->generateLabelFromSegment($segment, $context);
            
            $breadcrumbs[] = [
                'label' => $label,
                'url' => $currentPath,
                'icon' => $this->config['show_icons'] ? $this->guessIcon($segment) : null,
                'active' => $currentPath === $path
            ];
        }
        
        return $breadcrumbs;
    }

    /**
     * Generate label from URL segment
     */
    private function generateLabelFromSegment(string $segment, array $context): string
    {
        // Check if it's a numeric ID
        if (is_numeric($segment)) {
            return $this->getContextualLabel($segment, $context);
        }
        
        // Convert segment to readable label
        $label = str_replace(['-', '_'], ' ', $segment);
        $label = ucwords($label);
        
        // Try to get translation
        $translationKey = strtolower(str_replace(' ', '_', $label));
        return $this->getLabel($translationKey, $context, $label);
    }

    /**
     * Get contextual label for ID segments
     */
    private function getContextualLabel(string $id, array $context): string
    {
        // Try to get entity name from context
        if (isset($context['entity_name'])) {
            return $context['entity_name'];
        }
        
        if (isset($context['entity_title'])) {
            return $context['entity_title'];
        }
        
        // Try to get from previous segments or context
        if (isset($context['entity']) && isset($context['entity']['name'])) {
            return $context['entity']['name'];
        }
        
        return "#{$id}";
    }

    /**
     * Guess icon based on segment name
     */
    private function guessIcon(string $segment): string
    {
        $iconMap = [
            'users' => 'users',
            'user' => 'user',
            'roles' => 'shield',
            'role' => 'shield',
            'permissions' => 'key',
            'settings' => 'cog',
            'reports' => 'chart-bar',
            'dashboard' => 'home',
            'profile' => 'user-circle',
            'notifications' => 'bell',
            'messages' => 'mail',
            'files' => 'folder',
            'logs' => 'file-text',
            'system' => 'server',
            'new' => 'plus',
            'edit' => 'edit',
            'view' => 'eye',
            'delete' => 'trash'
        ];
        
        $segment = strtolower($segment);
        return $iconMap[$segment] ?? 'link';
    }

    /**
     * Get localized label
     */
    private function getLabel(string $key, array $context, string $fallback = null): string
    {
        $translation = $this->localizationService->get($key);
        
        if ($translation !== $key) {
            return $translation;
        }
        
        return $fallback ?: ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Add breadcrumb manually
     */
    public function add(string $label, string $url = null, string $icon = null): self
    {
        $this->breadcrumbs[] = [
            'label' => $label,
            'url' => $url,
            'icon' => $this->config['show_icons'] ? $icon : null,
            'active' => false
        ];
        
        return $this;
    }

    /**
     * Set last breadcrumb as active
     */
    public function setActive(): self
    {
        if (!empty($this->breadcrumbs)) {
            $lastIndex = count($this->breadcrumbs) - 1;
            $this->breadcrumbs[$lastIndex]['active'] = true;
        }
        
        return $this;
    }

    /**
     * Clear all breadcrumbs
     */
    public function clear(): self
    {
        $this->breadcrumbs = [];
        return $this;
    }

    /**
     * Get current breadcrumbs
     */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    /**
     * Render breadcrumbs HTML
     */
    public function render(array $breadcrumbs = null): string
    {
        $breadcrumbs = $breadcrumbs ?: $this->breadcrumbs;
        
        if (empty($breadcrumbs)) {
            return '';
        }

        $html = '<nav class="breadcrumb-nav" aria-label="Breadcrumb">';
        $html .= '<ol class="flex items-center space-x-2 text-sm text-gray-600">';
        
        foreach ($breadcrumbs as $index => $crumb) {
            $isLast = $index === count($breadcrumbs) - 1;
            $isActive = $crumb['active'] ?? $isLast;
            
            $html .= '<li class="flex items-center">';
            
            // Add separator if not first item
            if ($index > 0) {
                $html .= '<span class="mx-2 text-gray-400">' . htmlspecialchars($this->config['separator']) . '</span>';
            }
            
            // Add icon if available
            if ($crumb['icon'] && $this->config['show_icons']) {
                $html .= '<svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                $html .= $this->getIconPath($crumb['icon']);
                $html .= '</svg>';
            }
            
            // Add link or text
            if ($crumb['url'] && !$isActive) {
                $html .= '<a href="' . htmlspecialchars($crumb['url']) . '" class="hover:text-gray-900 transition-colors">';
                $html .= htmlspecialchars($this->truncateLabel($crumb['label']));
                $html .= '</a>';
            } else {
                $activeClass = $isActive ? 'text-gray-900 font-medium' : 'text-gray-600';
                $html .= '<span class="' . $activeClass . '">';
                $html .= htmlspecialchars($this->truncateLabel($crumb['label']));
                $html .= '</span>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        
        return $html;
    }

    /**
     * Truncate label if too long
     */
    private function truncateLabel(string $label): string
    {
        if (strlen($label) > $this->config['max_length']) {
            return substr($label, 0, $this->config['max_length'] - 3) . '...';
        }
        
        return $label;
    }

    /**
     * Get SVG icon path
     */
    private function getIconPath(string $icon): string
    {
        $icons = [
            'home' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 12 2-2m0 0 7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>',
            'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a4 4 0 11-8 0 4 4 0 018 0z"></path>',
            'user' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>',
            'shield' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>',
            'cog' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>',
            'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>',
            'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>',
            'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>',
            'link' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>'
        ];
        
        return $icons[$icon] ?? $icons['link'];
    }

    /**
     * Render breadcrumb schema markup
     */
    public function renderSchema(array $breadcrumbs = null): string
    {
        $breadcrumbs = $breadcrumbs ?: $this->breadcrumbs;
        
        if (empty($breadcrumbs)) {
            return '';
        }

        $items = [];
        foreach ($breadcrumbs as $index => $crumb) {
            if ($crumb['url']) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['label'],
                    'item' => $crumb['url']
                ];
            }
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
