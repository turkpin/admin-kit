<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;

class PerformanceService
{
    private CacheService $cacheService;
    private array $config;
    private array $metrics;
    private array $timers;
    private float $startTime;

    public function __construct(CacheService $cacheService, array $config = [])
    {
        $this->cacheService = $cacheService;
        $this->config = array_merge([
            'enabled' => true,
            'sample_rate' => 1.0, // 100% sampling
            'max_metrics' => 1000,
            'retention_hours' => 24,
            'slow_query_threshold' => 1.0, // seconds
            'memory_threshold' => 128 * 1024 * 1024, // 128MB
            'profiler_enabled' => false
        ], $config);
        
        $this->metrics = [];
        $this->timers = [];
        $this->startTime = microtime(true);
        
        if ($this->config['enabled']) {
            $this->startProfiler();
        }
    }

    /**
     * Start performance profiler
     */
    public function startProfiler(): void
    {
        register_shutdown_function([$this, 'recordRequestMetrics']);
        
        if ($this->config['profiler_enabled'] && extension_loaded('xdebug')) {
            xdebug_start_trace();
        }
    }

    /**
     * Start timing a operation
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    /**
     * Stop timing and record metric
     */
    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }
        
        $duration = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);
        
        $this->recordMetric('timer', $name, $duration);
        
        return $duration;
    }

    /**
     * Record a metric
     */
    public function recordMetric(string $type, string $name, $value, array $tags = []): void
    {
        if (!$this->config['enabled'] || !$this->shouldSample()) {
            return;
        }

        $metric = [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        $this->metrics[] = $metric;
        
        // Store in cache
        $this->storeMetric($metric);
    }

    /**
     * Record database query performance
     */
    public function recordQuery(string $query, float $duration, array $params = []): void
    {
        $this->recordMetric('query', 'database', $duration, [
            'query' => substr($query, 0, 200),
            'params_count' => count($params),
            'slow' => $duration > $this->config['slow_query_threshold']
        ]);
        
        if ($duration > $this->config['slow_query_threshold']) {
            $this->recordSlowQuery($query, $duration, $params);
        }
    }

    /**
     * Record slow query
     */
    private function recordSlowQuery(string $query, float $duration, array $params): void
    {
        $slowQuery = [
            'query' => $query,
            'duration' => $duration,
            'params' => $params,
            'timestamp' => time(),
            'memory' => memory_get_usage(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
        ];
        
        $key = 'slow_queries:' . date('Y-m-d-H');
        $queries = $this->cacheService->get($key, fn() => []);
        $queries[] = $slowQuery;
        
        // Keep only recent slow queries
        if (count($queries) > 100) {
            $queries = array_slice($queries, -100);
        }
        
        $this->cacheService->set($key, $queries, 86400);
    }

    /**
     * Record cache hit/miss
     */
    public function recordCacheHit(string $key, bool $hit): void
    {
        $this->recordMetric('cache', $hit ? 'hit' : 'miss', 1, ['key' => substr($key, 0, 50)]);
    }

    /**
     * Record memory usage
     */
    public function recordMemoryUsage(string $operation = 'general'): void
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        $this->recordMetric('memory', 'usage', $memory, ['operation' => $operation]);
        
        if ($memory > $this->config['memory_threshold']) {
            $this->recordHighMemoryUsage($operation, $memory, $peak);
        }
    }

    /**
     * Record high memory usage
     */
    private function recordHighMemoryUsage(string $operation, int $memory, int $peak): void
    {
        $alert = [
            'operation' => $operation,
            'memory' => $memory,
            'peak_memory' => $peak,
            'timestamp' => time(),
            'formatted_memory' => $this->formatBytes($memory),
            'formatted_peak' => $this->formatBytes($peak)
        ];
        
        $key = 'high_memory_usage:' . date('Y-m-d-H');
        $alerts = $this->cacheService->get($key, fn() => []);
        $alerts[] = $alert;
        
        $this->cacheService->set($key, $alerts, 86400);
    }

    /**
     * Record request metrics at shutdown
     */
    public function recordRequestMetrics(): void
    {
        $endTime = microtime(true);
        $duration = $endTime - $this->startTime;
        $memory = memory_get_peak_usage(true);
        
        $request = [
            'url' => $_SERVER['REQUEST_URI'] ?? '/',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'duration' => $duration,
            'memory' => $memory,
            'queries' => $this->getQueryCount(),
            'timestamp' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $this->getClientIp()
        ];
        
        $key = 'requests:' . date('Y-m-d-H');
        $requests = $this->cacheService->get($key, fn() => []);
        $requests[] = $request;
        
        // Keep only recent requests
        if (count($requests) > 1000) {
            $requests = array_slice($requests, -1000);
        }
        
        $this->cacheService->set($key, $requests, 86400);
        
        // Update hourly stats
        $this->updateHourlyStats($duration, $memory);
    }

    /**
     * Update hourly performance statistics
     */
    private function updateHourlyStats(float $duration, int $memory): void
    {
        $hour = date('Y-m-d-H');
        $key = "stats:{$hour}";
        
        $stats = $this->cacheService->get($key, fn() => [
            'requests' => 0,
            'total_duration' => 0,
            'total_memory' => 0,
            'max_duration' => 0,
            'max_memory' => 0,
            'min_duration' => PHP_FLOAT_MAX,
            'min_memory' => PHP_INT_MAX
        ]);
        
        $stats['requests']++;
        $stats['total_duration'] += $duration;
        $stats['total_memory'] += $memory;
        $stats['max_duration'] = max($stats['max_duration'], $duration);
        $stats['max_memory'] = max($stats['max_memory'], $memory);
        $stats['min_duration'] = min($stats['min_duration'], $duration);
        $stats['min_memory'] = min($stats['min_memory'], $memory);
        $stats['avg_duration'] = $stats['total_duration'] / $stats['requests'];
        $stats['avg_memory'] = $stats['total_memory'] / $stats['requests'];
        
        $this->cacheService->set($key, $stats, 86400);
    }

    /**
     * Get performance dashboard data
     */
    public function getDashboardData(int $hours = 24): array
    {
        $data = [
            'current_stats' => $this->getCurrentStats(),
            'hourly_stats' => $this->getHourlyStats($hours),
            'slow_queries' => $this->getSlowQueries($hours),
            'high_memory_usage' => $this->getHighMemoryUsage($hours),
            'cache_stats' => $this->getCacheStats(),
            'system_info' => $this->getSystemInfo()
        ];
        
        return $data;
    }

    /**
     * Get current system statistics
     */
    private function getCurrentStats(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'memory_limit' => $this->parseBytes(ini_get('memory_limit')),
            'execution_time' => microtime(true) - $this->startTime,
            'max_execution_time' => ini_get('max_execution_time'),
            'loaded_extensions' => count(get_loaded_extensions()),
            'included_files' => count(get_included_files())
        ];
    }

    /**
     * Get hourly statistics
     */
    private function getHourlyStats(int $hours): array
    {
        $stats = [];
        
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = date('Y-m-d-H', strtotime("-{$i} hours"));
            $key = "stats:{$hour}";
            $hourStats = $this->cacheService->get($key, fn() => null);
            
            if ($hourStats) {
                $stats[$hour] = $hourStats;
            }
        }
        
        return $stats;
    }

    /**
     * Get slow queries
     */
    private function getSlowQueries(int $hours): array
    {
        $queries = [];
        
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = date('Y-m-d-H', strtotime("-{$i} hours"));
            $key = "slow_queries:{$hour}";
            $hourQueries = $this->cacheService->get($key, fn() => []);
            $queries = array_merge($queries, $hourQueries);
        }
        
        // Sort by duration descending
        usort($queries, fn($a, $b) => $b['duration'] <=> $a['duration']);
        
        return array_slice($queries, 0, 50);
    }

    /**
     * Get high memory usage alerts
     */
    private function getHighMemoryUsage(int $hours): array
    {
        $alerts = [];
        
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = date('Y-m-d-H', strtotime("-{$i} hours"));
            $key = "high_memory_usage:{$hour}";
            $hourAlerts = $this->cacheService->get($key, fn() => []);
            $alerts = array_merge($alerts, $hourAlerts);
        }
        
        // Sort by memory descending
        usort($alerts, fn($a, $b) => $b['memory'] <=> $a['memory']);
        
        return array_slice($alerts, 0, 20);
    }

    /**
     * Get cache statistics
     */
    private function getCacheStats(): array
    {
        // Get recent cache metrics
        $hour = date('Y-m-d-H');
        $key = "cache_stats:{$hour}";
        
        return $this->cacheService->get($key, fn() => [
            'hits' => 0,
            'misses' => 0,
            'hit_rate' => 0
        ]);
    }

    /**
     * Get system information
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
            'xdebug_enabled' => extension_loaded('xdebug'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'loaded_extensions' => get_loaded_extensions()
        ];
    }

    /**
     * Store metric in cache
     */
    private function storeMetric(array $metric): void
    {
        $hour = date('Y-m-d-H', (int)$metric['timestamp']);
        $key = "metrics:{$hour}";
        
        $metrics = $this->cacheService->get($key, fn() => []);
        $metrics[] = $metric;
        
        // Keep only recent metrics
        if (count($metrics) > $this->config['max_metrics']) {
            $metrics = array_slice($metrics, -$this->config['max_metrics']);
        }
        
        $this->cacheService->set($key, $metrics, 86400);
    }

    /**
     * Get query count (placeholder)
     */
    private function getQueryCount(): int
    {
        // In real implementation, track query count
        return 0;
    }

    /**
     * Get client IP
     */
    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'unknown';
    }

    /**
     * Check if should sample this request
     */
    private function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() < $this->config['sample_rate'];
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Parse human readable bytes to integer
     */
    private function parseBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int)$val;
        
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }

    /**
     * Render performance dashboard
     */
    public function renderDashboard(): string
    {
        $data = $this->getDashboardData();
        $current = $data['current_stats'];
        $system = $data['system_info'];
        
        return '
        <div class="performance-dashboard">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Performance Monitor</h2>
                
                <!-- Current Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-blue-600">' . $this->formatBytes($current['memory_usage']) . '</div>
                        <div class="text-sm text-gray-600">Current Memory</div>
                        <div class="text-xs text-gray-500">Limit: ' . $system['memory_limit'] . '</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-green-600">' . round($current['execution_time'] * 1000) . 'ms</div>
                        <div class="text-sm text-gray-600">Execution Time</div>
                        <div class="text-xs text-gray-500">Max: ' . $system['max_execution_time'] . 's</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-purple-600">' . $current['included_files'] . '</div>
                        <div class="text-sm text-gray-600">Included Files</div>
                        <div class="text-xs text-gray-500">Extensions: ' . $current['loaded_extensions'] . '</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-orange-600">' . $system['php_version'] . '</div>
                        <div class="text-sm text-gray-600">PHP Version</div>
                        <div class="text-xs text-gray-500">OPcache: ' . ($system['opcache_enabled'] ? 'ON' : 'OFF') . '</div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Memory Usage Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Memory Usage Trend</h3>
                    <div class="h-64 flex items-end justify-between space-x-1">';
        
        // Generate sample memory chart
        for ($i = 23; $i >= 0; $i--) {
            $height = rand(20, 80);
            $hour = date('H', strtotime("-{$i} hours"));
            $color = $height > 60 ? 'bg-red-500' : ($height > 40 ? 'bg-yellow-500' : 'bg-green-500');
            
            $html .= '<div class="flex-1 ' . $color . ' rounded-t" style="height: ' . $height . '%" title="' . $hour . ':00"></div>';
        }
        
        $html .= '
                    </div>
                    <div class="mt-2 text-xs text-gray-500 text-center">Last 24 hours</div>
                </div>
                
                <!-- Response Time Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Response Time Trend</h3>
                    <div class="h-64 flex items-end justify-between space-x-1">';
        
        // Generate sample response time chart
        for ($i = 23; $i >= 0; $i--) {
            $height = rand(15, 90);
            $hour = date('H', strtotime("-{$i} hours"));
            $color = $height > 70 ? 'bg-red-500' : ($height > 40 ? 'bg-yellow-500' : 'bg-blue-500');
            
            $html .= '<div class="flex-1 ' . $color . ' rounded-t" style="height: ' . $height . '%" title="' . $hour . ':00"></div>';
        }
        
        $html .= '
                    </div>
                    <div class="mt-2 text-xs text-gray-500 text-center">Last 24 hours</div>
                </div>
                
                <!-- Slow Queries -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Slow Queries</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">';
        
        foreach (array_slice($data['slow_queries'], 0, 10) as $query) {
            $html .= '
                        <div class="border-l-4 border-red-500 pl-3 py-2 bg-red-50">
                            <div class="text-sm font-medium text-red-800">' . round($query['duration'] * 1000) . 'ms</div>
                            <div class="text-xs text-red-600 truncate">' . htmlspecialchars(substr($query['query'], 0, 100)) . '...</div>
                            <div class="text-xs text-red-500">' . date('H:i:s', $query['timestamp']) . '</div>
                        </div>';
        }
        
        if (empty($data['slow_queries'])) {
            $html .= '<div class="text-center text-gray-500 py-8">No slow queries detected</div>';
        }
        
        $html .= '
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">System Information</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Server:</span>
                            <span class="font-medium">' . htmlspecialchars($system['server_software']) . '</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">PHP Version:</span>
                            <span class="font-medium">' . $system['php_version'] . '</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Memory Limit:</span>
                            <span class="font-medium">' . $system['memory_limit'] . '</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Upload Limit:</span>
                            <span class="font-medium">' . $system['upload_max_filesize'] . '</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">OPcache:</span>
                            <span class="font-medium ' . ($system['opcache_enabled'] ? 'text-green-600' : 'text-red-600') . '">' . ($system['opcache_enabled'] ? 'Enabled' : 'Disabled') . '</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Extensions:</span>
                            <span class="font-medium">' . count($system['loaded_extensions']) . ' loaded</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Actions</h3>
                <div class="flex space-x-4">
                    <button onclick="clearCache()" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                        Clear Cache
                    </button>
                    <button onclick="optimizeDatabase()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Optimize Database
                    </button>
                    <button onclick="generateReport()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Generate Report
                    </button>
                    <button onclick="exportMetrics()" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Export Metrics
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        function clearCache() {
            if (confirm("Clear all cache data?")) {
                fetch("/admin/performance/clear-cache", { method: "POST" })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.success ? "Cache cleared" : "Failed to clear cache");
                        location.reload();
                    })
                    .catch(error => alert("Error: " + error.message));
            }
        }
        
        function optimizeDatabase() {
            if (confirm("Optimize database tables?")) {
                fetch("/admin/performance/optimize-db", { method: "POST" })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message || "Database optimization completed");
                    })
                    .catch(error => alert("Error: " + error.message));
            }
        }
        
        function generateReport() {
            window.open("/admin/performance/report", "_blank");
        }
        
        function exportMetrics() {
            window.location.href = "/admin/performance/export";
        }
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
        </script>';
        
        return $html;
    }
}
