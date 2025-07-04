<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turkpin\AdminKit\Services\AuthService;
use Turkpin\AdminKit\Services\SmartyService;

class DashboardController
{
    private AuthService $authService;
    private SmartyService $smartyService;
    private array $config;
    private array $entities;
    private array $widgets;

    public function __construct(
        AuthService $authService, 
        SmartyService $smartyService, 
        array $config,
        array $entities = [],
        array $widgets = []
    ) {
        $this->authService = $authService;
        $this->smartyService = $smartyService;
        $this->config = $config;
        $this->entities = $entities;
        $this->widgets = $widgets;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $currentUser = $this->authService->getCurrentUser();
        
        // Widget değerlerini hesapla
        $processedWidgets = $this->processWidgets();
        
        // Breadcrumb oluştur
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '#']
        ];

        $data = [
            'page_title' => $this->config['dashboard_title'] ?? 'Dashboard',
            'current_user' => $currentUser,
            'entities' => $this->entities,
            'widgets' => $processedWidgets,
            'breadcrumbs' => $breadcrumbs,
            'stats' => $this->getSystemStats(),
            'recent_activities' => $this->getRecentActivities(),
            'quick_actions' => $this->getQuickActions(),
        ];

        $html = $this->smartyService->render('dashboard/index.tpl', $data);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    private function processWidgets(): array
    {
        $processed = [];
        
        foreach ($this->widgets as $name => $widget) {
            $processed[$name] = $widget;
            
            // Callable value'ları çağır
            if (isset($widget['value']) && is_callable($widget['value'])) {
                try {
                    $processed[$name]['value'] = call_user_func($widget['value']);
                } catch (\Exception $e) {
                    error_log("Widget '{$name}' value calculation error: " . $e->getMessage());
                    $processed[$name]['value'] = 'Hata';
                }
            }
            
            // Default değerleri kontrol et
            $processed[$name]['icon'] = $widget['icon'] ?? 'chart-bar';
            $processed[$name]['color'] = $widget['color'] ?? 'blue';
            $processed[$name]['type'] = $widget['type'] ?? 'stat';
        }
        
        return $processed;
    }

    private function getSystemStats(): array
    {
        try {
            $entityManager = $this->authService->getEntityManager();
            
            return [
                'php_version' => PHP_VERSION,
                'adminkit_version' => '1.0.0',
                'total_users' => $this->getUserCount($entityManager),
                'active_users' => $this->getActiveUserCount($entityManager),
                'total_roles' => $this->getRoleCount($entityManager),
                'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
                'server_time' => date('Y-m-d H:i:s'),
            ];
        } catch (\Exception $e) {
            error_log('System stats error: ' . $e->getMessage());
            return [
                'php_version' => PHP_VERSION,
                'adminkit_version' => '1.0.0',
                'server_time' => date('Y-m-d H:i:s'),
            ];
        }
    }

    private function getUserCount($entityManager): int
    {
        try {
            return $entityManager->createQuery(
                'SELECT COUNT(u.id) FROM Turkpin\AdminKit\Entities\User u'
            )->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getActiveUserCount($entityManager): int
    {
        try {
            return $entityManager->createQuery(
                'SELECT COUNT(u.id) FROM Turkpin\AdminKit\Entities\User u WHERE u.isActive = :active'
            )->setParameter('active', true)->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRoleCount($entityManager): int
    {
        try {
            return $entityManager->createQuery(
                'SELECT COUNT(r.id) FROM Turkpin\AdminKit\Entities\Role r'
            )->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRecentActivities(): array
    {
        // Şimdilik statik veriler, ilerleyen zamanda audit log'dan alınacak
        return [
            [
                'user' => 'Admin',
                'action' => 'Yeni kullanıcı oluşturdu',
                'target' => 'test@example.com',
                'time' => '5 dakika önce',
                'type' => 'create'
            ],
            [
                'user' => 'Admin',
                'action' => 'Kullanıcı rolü güncelledi',
                'target' => 'editor@example.com',
                'time' => '1 saat önce',
                'type' => 'update'
            ],
            [
                'user' => 'Editor',
                'action' => 'Sisteme giriş yaptı',
                'target' => '',
                'time' => '2 saat önce',
                'type' => 'login'
            ],
        ];
    }

    private function getQuickActions(): array
    {
        $actions = [];
        
        // Entity'ler için hızlı aksiyon oluştur
        foreach ($this->entities as $entityName => $entityConfig) {
            if (in_array('new', $entityConfig['actions'])) {
                // Yetki kontrolü
                if ($this->canUserAccess($entityName, 'new')) {
                    $actions[] = [
                        'title' => 'Yeni ' . $entityConfig['title'],
                        'url' => $this->config['route_prefix'] . '/' . $entityName . '/new',
                        'icon' => 'plus',
                        'color' => 'green'
                    ];
                }
            }
        }

        // Sistem aksiyonları
        if ($this->authService->hasRole('admin')) {
            $actions[] = [
                'title' => 'Sistem Ayarları',
                'url' => $this->config['route_prefix'] . '/settings',
                'icon' => 'cog',
                'color' => 'gray'
            ];
        }

        return $actions;
    }

    private function canUserAccess(string $entityName, string $action): bool
    {
        try {
            return $this->authService->canAccess($entityName, $action);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
