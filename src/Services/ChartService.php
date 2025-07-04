<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;
use Doctrine\ORM\EntityManagerInterface;

class ChartService
{
    private CacheService $cacheService;
    private EntityManagerInterface $entityManager;
    private array $config;
    private array $registeredCharts;

    public function __construct(
        CacheService $cacheService,
        EntityManagerInterface $entityManager,
        array $config = []
    ) {
        $this->cacheService = $cacheService;
        $this->entityManager = $entityManager;
        $this->config = array_merge([
            'cache_ttl' => 600, // 10 minutes
            'default_theme' => 'light',
            'responsive' => true,
            'animation' => true
        ], $config);
        
        $this->registeredCharts = [];
        $this->registerDefaultCharts();
    }

    /**
     * Register default chart types
     */
    private function registerDefaultCharts(): void
    {
        $this->registerChart('line', [
            'name' => 'Line Chart',
            'description' => 'Display trends over time',
            'icon' => 'chart-line',
            'config' => [
                'type' => 'line',
                'responsive' => true,
                'scales' => [
                    'x' => ['type' => 'time'],
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ]);

        $this->registerChart('bar', [
            'name' => 'Bar Chart',
            'description' => 'Compare values across categories',
            'icon' => 'chart-bar',
            'config' => [
                'type' => 'bar',
                'responsive' => true,
                'scales' => [
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ]);

        $this->registerChart('pie', [
            'name' => 'Pie Chart',
            'description' => 'Show proportions of a whole',
            'icon' => 'chart-pie',
            'config' => [
                'type' => 'pie',
                'responsive' => true,
                'plugins' => [
                    'legend' => ['position' => 'bottom']
                ]
            ]
        ]);

        $this->registerChart('doughnut', [
            'name' => 'Doughnut Chart',
            'description' => 'Modern pie chart with center hole',
            'icon' => 'chart-donut',
            'config' => [
                'type' => 'doughnut',
                'responsive' => true,
                'plugins' => [
                    'legend' => ['position' => 'bottom']
                ]
            ]
        ]);

        $this->registerChart('area', [
            'name' => 'Area Chart',
            'description' => 'Line chart with filled areas',
            'icon' => 'chart-area',
            'config' => [
                'type' => 'line',
                'responsive' => true,
                'fill' => true,
                'scales' => [
                    'x' => ['type' => 'time'],
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ]);
    }

    /**
     * Register a new chart type
     */
    public function registerChart(string $type, array $config): void
    {
        $this->registeredCharts[$type] = $config;
    }

    /**
     * Generate chart data for users over time
     */
    public function getUserRegistrationChart(array $options = []): array
    {
        $cacheKey = 'chart_user_registration_' . md5(serialize($options));
        
        return $this->cacheService->remember($cacheKey, function() use ($options) {
            $days = $options['days'] ?? 30;
            $startDate = new \DateTime("-{$days} days");
            
            try {
                $query = $this->entityManager->createQuery(
                    'SELECT DATE(u.createdAt) as date, COUNT(u.id) as count 
                     FROM Turkpin\AdminKit\Entities\User u 
                     WHERE u.createdAt >= :startDate 
                     GROUP BY DATE(u.createdAt) 
                     ORDER BY date ASC'
                );
                $query->setParameter('startDate', $startDate);
                $results = $query->getArrayResult();
                
                // Fill missing dates with zero
                $data = [];
                $labels = [];
                $currentDate = clone $startDate;
                $today = new \DateTime();
                
                while ($currentDate <= $today) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $labels[] = $currentDate->format('M j');
                    
                    $found = false;
                    foreach ($results as $result) {
                        if ($result['date'] === $dateStr) {
                            $data[] = (int)$result['count'];
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $data[] = 0;
                    }
                    
                    $currentDate->modify('+1 day');
                }
                
                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'New Users',
                            'data' => $data,
                            'borderColor' => '#3b82f6',
                            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                            'tension' => 0.4
                        ]
                    ]
                ];
                
            } catch (\Exception $e) {
                return $this->getEmptyChartData('New Users');
            }
        }, $this->config['cache_ttl']);
    }

    /**
     * Generate role distribution pie chart
     */
    public function getRoleDistributionChart(): array
    {
        $cacheKey = 'chart_role_distribution';
        
        return $this->cacheService->remember($cacheKey, function() {
            try {
                $query = $this->entityManager->createQuery(
                    'SELECT r.name, COUNT(u.id) as count 
                     FROM Turkpin\AdminKit\Entities\Role r 
                     LEFT JOIN r.users u 
                     GROUP BY r.id 
                     ORDER BY count DESC'
                );
                $results = $query->getArrayResult();
                
                $labels = [];
                $data = [];
                $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#f97316'];
                $backgroundColors = [];
                
                foreach ($results as $index => $result) {
                    $labels[] = $result['name'];
                    $data[] = (int)$result['count'];
                    $backgroundColors[] = $colors[$index % count($colors)];
                }
                
                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'data' => $data,
                            'backgroundColor' => $backgroundColors,
                            'borderWidth' => 2,
                            'borderColor' => '#ffffff'
                        ]
                    ]
                ];
                
            } catch (\Exception $e) {
                return $this->getEmptyChartData('Roles');
            }
        }, $this->config['cache_ttl']);
    }

    /**
     * Generate login activity chart
     */
    public function getLoginActivityChart(array $options = []): array
    {
        $cacheKey = 'chart_login_activity_' . md5(serialize($options));
        
        return $this->cacheService->remember($cacheKey, function() use ($options) {
            $hours = $options['hours'] ?? 24;
            
            // Since we don't have login logs yet, generate sample data
            $labels = [];
            $data = [];
            
            for ($i = $hours; $i >= 0; $i--) {
                $time = new \DateTime("-{$i} hours");
                $labels[] = $time->format('H:i');
                // Sample data - in real implementation, this would come from audit logs
                $data[] = rand(0, 20);
            }
            
            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Logins',
                        'data' => $data,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'tension' => 0.4
                    ]
                ]
            ];
        }, $this->config['cache_ttl']);
    }

    /**
     * Generate entity statistics chart
     */
    public function getEntityStatsChart(): array
    {
        $cacheKey = 'chart_entity_stats';
        
        return $this->cacheService->remember($cacheKey, function() {
            $entities = [
                'Users' => 'Turkpin\AdminKit\Entities\User',
                'Roles' => 'Turkpin\AdminKit\Entities\Role',
                'Permissions' => 'Turkpin\AdminKit\Entities\Permission'
            ];
            
            $labels = [];
            $data = [];
            
            foreach ($entities as $label => $entityClass) {
                try {
                    $count = $this->entityManager->createQuery(
                        "SELECT COUNT(e.id) FROM {$entityClass} e"
                    )->getSingleScalarResult();
                    
                    $labels[] = $label;
                    $data[] = (int)$count;
                } catch (\Exception $e) {
                    $labels[] = $label;
                    $data[] = 0;
                }
            }
            
            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Count',
                        'data' => $data,
                        'backgroundColor' => [
                            '#3b82f6',
                            '#ef4444',
                            '#10b981'
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ];
        }, $this->config['cache_ttl']);
    }

    /**
     * Render chart widget
     */
    public function renderChart(string $type, array $data, array $options = []): string
    {
        $chartId = 'chart_' . uniqid();
        $config = $this->getChartConfig($type, $data, $options);
        
        $html = '<div class="chart-widget">';
        
        // Chart container
        $html .= '<div class="chart-container" style="position: relative; height: ' . ($options['height'] ?? '400px') . ';">';
        $html .= '<canvas id="' . $chartId . '"></canvas>';
        $html .= '</div>';
        
        // Chart.js script
        $html .= '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        $html .= '<script>';
        $html .= 'document.addEventListener("DOMContentLoaded", function() {';
        $html .= '  const ctx = document.getElementById("' . $chartId . '").getContext("2d");';
        $html .= '  new Chart(ctx, ' . json_encode($config) . ');';
        $html .= '});';
        $html .= '</script>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render dashboard widget with chart
     */
    public function renderDashboardWidget(string $title, string $chartType, array $data, array $options = []): string
    {
        $widgetId = 'widget_' . uniqid();
        
        $html = '<div class="dashboard-widget bg-white rounded-lg shadow p-6" id="' . $widgetId . '">';
        
        // Widget header
        $html .= '<div class="widget-header flex items-center justify-between mb-4">';
        $html .= '<h3 class="text-lg font-medium text-gray-900">' . htmlspecialchars($title) . '</h3>';
        
        // Widget actions
        $html .= '<div class="widget-actions flex space-x-2">';
        $html .= '<button onclick="refreshWidget(\'' . $widgetId . '\')" class="text-gray-400 hover:text-gray-600" title="Refresh">';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Chart
        $html .= $this->renderChart($chartType, $data, $options);
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get chart configuration
     */
    private function getChartConfig(string $type, array $data, array $options = []): array
    {
        $baseConfig = $this->registeredCharts[$type]['config'] ?? [];
        
        $config = array_merge([
            'data' => $data,
            'options' => [
                'responsive' => $this->config['responsive'],
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => $options['showLegend'] ?? true
                    ],
                    'tooltip' => [
                        'enabled' => true,
                        'mode' => 'index',
                        'intersect' => false
                    ]
                ]
            ]
        ], $baseConfig);
        
        // Merge custom options
        if (isset($options['chartOptions'])) {
            $config['options'] = array_merge_recursive($config['options'], $options['chartOptions']);
        }
        
        // Apply theme
        $this->applyTheme($config, $options['theme'] ?? $this->config['default_theme']);
        
        return $config;
    }

    /**
     * Apply theme to chart config
     */
    private function applyTheme(array &$config, string $theme): void
    {
        $themes = [
            'light' => [
                'backgroundColor' => '#ffffff',
                'gridColor' => '#e5e7eb',
                'textColor' => '#374151'
            ],
            'dark' => [
                'backgroundColor' => '#1f2937',
                'gridColor' => '#374151',
                'textColor' => '#f9fafb'
            ]
        ];
        
        $themeConfig = $themes[$theme] ?? $themes['light'];
        
        // Apply grid colors
        if (isset($config['options']['scales'])) {
            foreach ($config['options']['scales'] as &$scale) {
                $scale['grid']['color'] = $themeConfig['gridColor'];
                $scale['ticks']['color'] = $themeConfig['textColor'];
            }
        }
        
        // Apply legend colors
        if (isset($config['options']['plugins']['legend'])) {
            $config['options']['plugins']['legend']['labels']['color'] = $themeConfig['textColor'];
        }
    }

    /**
     * Get empty chart data for error cases
     */
    private function getEmptyChartData(string $label): array
    {
        return [
            'labels' => ['No Data'],
            'datasets' => [
                [
                    'label' => $label,
                    'data' => [0],
                    'backgroundColor' => '#e5e7eb'
                ]
            ]
        ];
    }

    /**
     * Get available chart types
     */
    public function getAvailableChartTypes(): array
    {
        return array_map(function($chart, $type) {
            return [
                'type' => $type,
                'name' => $chart['name'],
                'description' => $chart['description'],
                'icon' => $chart['icon']
            ];
        }, $this->registeredCharts, array_keys($this->registeredCharts));
    }

    /**
     * Export chart data as JSON
     */
    public function exportChartData(string $chartType, array $options = []): array
    {
        switch ($chartType) {
            case 'user_registration':
                return $this->getUserRegistrationChart($options);
            case 'role_distribution':
                return $this->getRoleDistributionChart();
            case 'login_activity':
                return $this->getLoginActivityChart($options);
            case 'entity_stats':
                return $this->getEntityStatsChart();
            default:
                return $this->getEmptyChartData('Unknown Chart');
        }
    }

    /**
     * Clear chart cache
     */
    public function clearCache(string $chartType = null): void
    {
        if ($chartType) {
            $this->cacheService->clearPattern("chart_{$chartType}_*");
        } else {
            $this->cacheService->clearPattern('chart_*');
        }
    }

    /**
     * Generate JavaScript for refreshing widgets
     */
    public function renderWidgetScript(): string
    {
        return "
        <script>
        function refreshWidget(widgetId) {
            const widget = document.getElementById(widgetId);
            const canvas = widget.querySelector('canvas');
            
            if (!canvas) return;
            
            // Show loading state
            const overlay = document.createElement('div');
            overlay.className = 'absolute inset-0 bg-gray-50 bg-opacity-75 flex items-center justify-center';
            overlay.innerHTML = '<div class=\"animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500\"></div>';
            widget.style.position = 'relative';
            widget.appendChild(overlay);
            
            // Simulate refresh (in real implementation, this would make an API call)
            setTimeout(() => {
                overlay.remove();
                // Here you would update the chart with new data
                console.log('Widget refreshed:', widgetId);
            }, 1000);
        }
        
        // Auto-refresh widgets every 5 minutes
        setInterval(() => {
            const widgets = document.querySelectorAll('.dashboard-widget[id^=\"widget_\"]');
            widgets.forEach(widget => {
                if (widget.dataset.autoRefresh !== 'false') {
                    // Stagger refreshes to avoid server overload
                    setTimeout(() => refreshWidget(widget.id), Math.random() * 10000);
                }
            });
        }, 300000); // 5 minutes
        </script>";
    }
}
