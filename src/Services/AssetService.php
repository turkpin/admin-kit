<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;

class AssetService
{
    private CacheService $cacheService;
    private array $config;
    private array $assets;
    private array $manifests;
    private string $publicPath;

    public function __construct(CacheService $cacheService, array $config = [])
    {
        $this->cacheService = $cacheService;
        $this->config = array_merge([
            'enabled' => true,
            'public_path' => 'public/assets',
            'url_prefix' => '/assets',
            'versioning' => true,
            'minification' => true,
            'compression' => true,
            'cdn_enabled' => false,
            'cdn_url' => '',
            'cache_busting' => true,
            'dev_mode' => false,
            'build_path' => 'build',
            'source_maps' => false,
            'hot_reload' => false
        ], $config);
        
        $this->assets = [];
        $this->manifests = [];
        $this->publicPath = rtrim($this->config['public_path'], '/');
        
        $this->loadManifests();
        $this->registerDefaultAssets();
    }

    /**
     * Load asset manifests
     */
    private function loadManifests(): void
    {
        $manifestFiles = [
            'manifest.json',
            'mix-manifest.json',
            'vite-manifest.json',
            'webpack-manifest.json'
        ];

        foreach ($manifestFiles as $file) {
            $path = $this->publicPath . '/' . $file;
            if (file_exists($path)) {
                $manifest = json_decode(file_get_contents($path), true);
                if ($manifest) {
                    $this->manifests[pathinfo($file, PATHINFO_FILENAME)] = $manifest;
                }
            }
        }
    }

    /**
     * Register default admin assets
     */
    private function registerDefaultAssets(): void
    {
        // CSS Assets
        $this->registerAsset('admin-core.css', [
            'type' => 'css',
            'path' => 'css/admin-core.css',
            'dependencies' => [],
            'priority' => 100,
            'inline' => false,
            'critical' => true
        ]);

        $this->registerAsset('admin-theme.css', [
            'type' => 'css',
            'path' => 'css/themes/default.css',
            'dependencies' => ['admin-core.css'],
            'priority' => 90,
            'inline' => false
        ]);

        $this->registerAsset('components.css', [
            'type' => 'css',
            'path' => 'css/components.css',
            'dependencies' => ['admin-core.css'],
            'priority' => 80
        ]);

        // JavaScript Assets
        $this->registerAsset('admin-core.js', [
            'type' => 'js',
            'path' => 'js/admin-core.js',
            'dependencies' => [],
            'priority' => 100,
            'defer' => true,
            'module' => false
        ]);

        $this->registerAsset('components.js', [
            'type' => 'js',
            'path' => 'js/components.js',
            'dependencies' => ['admin-core.js'],
            'priority' => 90,
            'defer' => true
        ]);

        $this->registerAsset('charts.js', [
            'type' => 'js',
            'path' => 'js/charts.js',
            'dependencies' => ['admin-core.js'],
            'priority' => 70,
            'defer' => true,
            'external' => true,
            'src' => 'https://cdn.jsdelivr.net/npm/chart.js'
        ]);

        // Vendor Assets
        $this->registerAsset('tailwind.css', [
            'type' => 'css',
            'path' => 'vendor/tailwindcss/tailwind.min.css',
            'priority' => 110,
            'external' => true,
            'src' => 'https://cdn.tailwindcss.com'
        ]);

        $this->registerAsset('alpine.js', [
            'type' => 'js',
            'path' => 'vendor/alpinejs/alpine.min.js',
            'priority' => 110,
            'defer' => true,
            'external' => true,
            'src' => 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js'
        ]);
    }

    /**
     * Register an asset
     */
    public function registerAsset(string $name, array $config): void
    {
        $this->assets[$name] = array_merge([
            'type' => 'css',
            'path' => '',
            'dependencies' => [],
            'priority' => 50,
            'inline' => false,
            'defer' => false,
            'async' => false,
            'module' => false,
            'critical' => false,
            'external' => false,
            'src' => null,
            'integrity' => null,
            'crossorigin' => null
        ], $config);
    }

    /**
     * Get asset URL with versioning
     */
    public function getAssetUrl(string $name): string
    {
        if (!isset($this->assets[$name])) {
            return '';
        }

        $asset = $this->assets[$name];

        // Use external source if available
        if ($asset['external'] && $asset['src']) {
            return $asset['src'];
        }

        $path = $asset['path'];

        // Check manifest for versioned path
        $versionedPath = $this->getVersionedPath($path);
        if ($versionedPath) {
            $path = $versionedPath;
        }

        // Build URL
        $url = $this->config['url_prefix'] . '/' . ltrim($path, '/');

        // Add CDN if enabled
        if ($this->config['cdn_enabled'] && $this->config['cdn_url']) {
            $url = rtrim($this->config['cdn_url'], '/') . $url;
        }

        // Add cache busting
        if ($this->config['cache_busting'] && !$this->hasVersionInPath($path)) {
            $url .= '?v=' . $this->getAssetVersion($name);
        }

        return $url;
    }

    /**
     * Get versioned path from manifests
     */
    private function getVersionedPath(string $path): ?string
    {
        foreach ($this->manifests as $manifest) {
            if (isset($manifest[$path])) {
                return $manifest[$path];
            }
            
            // Try with leading slash
            $keyWithSlash = '/' . ltrim($path, '/');
            if (isset($manifest[$keyWithSlash])) {
                return ltrim($manifest[$keyWithSlash], '/');
            }
        }

        return null;
    }

    /**
     * Check if path already has version
     */
    private function hasVersionInPath(string $path): bool
    {
        return preg_match('/\.[a-f0-9]{8,}\.(css|js)$/', $path);
    }

    /**
     * Get asset version for cache busting
     */
    private function getAssetVersion(string $name): string
    {
        if (!isset($this->assets[$name])) {
            return '1';
        }

        $asset = $this->assets[$name];
        $filePath = $this->publicPath . '/' . $asset['path'];

        if (file_exists($filePath)) {
            return substr(md5_file($filePath), 0, 8);
        }

        return '1';
    }

    /**
     * Render CSS assets
     */
    public function renderCss(array $assets = null): string
    {
        $cssAssets = $this->getCssAssets($assets);
        $html = '';

        foreach ($cssAssets as $name => $asset) {
            if ($asset['inline']) {
                $html .= $this->renderInlineCss($name, $asset);
            } else {
                $html .= $this->renderCssLink($name, $asset);
            }
        }

        return $html;
    }

    /**
     * Render JavaScript assets
     */
    public function renderJs(array $assets = null): string
    {
        $jsAssets = $this->getJsAssets($assets);
        $html = '';

        foreach ($jsAssets as $name => $asset) {
            if ($asset['inline']) {
                $html .= $this->renderInlineJs($name, $asset);
            } else {
                $html .= $this->renderJsScript($name, $asset);
            }
        }

        return $html;
    }

    /**
     * Get CSS assets sorted by priority and dependencies
     */
    private function getCssAssets(array $assets = null): array
    {
        $allAssets = $assets ? array_intersect_key($this->assets, array_flip($assets)) : $this->assets;
        $cssAssets = array_filter($allAssets, fn($asset) => $asset['type'] === 'css');
        
        return $this->sortAssetsByDependencies($cssAssets);
    }

    /**
     * Get JavaScript assets sorted by priority and dependencies
     */
    private function getJsAssets(array $assets = null): array
    {
        $allAssets = $assets ? array_intersect_key($this->assets, array_flip($assets)) : $this->assets;
        $jsAssets = array_filter($allAssets, fn($asset) => $asset['type'] === 'js');
        
        return $this->sortAssetsByDependencies($jsAssets);
    }

    /**
     * Sort assets by dependencies and priority
     */
    private function sortAssetsByDependencies(array $assets): array
    {
        $sorted = [];
        $processed = [];

        $processDependencies = function($name, $asset) use (&$processDependencies, &$sorted, &$processed, $assets) {
            if (isset($processed[$name])) {
                return;
            }

            // Process dependencies first
            foreach ($asset['dependencies'] as $dependency) {
                if (isset($assets[$dependency])) {
                    $processDependencies($dependency, $assets[$dependency]);
                }
            }

            $sorted[$name] = $asset;
            $processed[$name] = true;
        };

        // Sort by priority first
        uasort($assets, fn($a, $b) => $b['priority'] - $a['priority']);

        foreach ($assets as $name => $asset) {
            $processDependencies($name, $asset);
        }

        return $sorted;
    }

    /**
     * Render CSS link tag
     */
    private function renderCssLink(string $name, array $asset): string
    {
        $url = $this->getAssetUrl($name);
        $attributes = [
            'rel' => 'stylesheet',
            'href' => $url
        ];

        if ($asset['integrity']) {
            $attributes['integrity'] = $asset['integrity'];
        }

        if ($asset['crossorigin']) {
            $attributes['crossorigin'] = $asset['crossorigin'];
        }

        $attributeString = $this->buildAttributeString($attributes);
        return "<link {$attributeString}>\n";
    }

    /**
     * Render inline CSS
     */
    private function renderInlineCss(string $name, array $asset): string
    {
        $filePath = $this->publicPath . '/' . $asset['path'];
        
        if (!file_exists($filePath)) {
            return "<!-- CSS file not found: {$asset['path']} -->\n";
        }

        $css = file_get_contents($filePath);
        
        if ($this->config['minification'] && !$this->config['dev_mode']) {
            $css = $this->minifyCss($css);
        }

        return "<style>\n{$css}\n</style>\n";
    }

    /**
     * Render JavaScript script tag
     */
    private function renderJsScript(string $name, array $asset): string
    {
        $url = $this->getAssetUrl($name);
        $attributes = [
            'src' => $url
        ];

        if ($asset['defer']) {
            $attributes['defer'] = 'defer';
        }

        if ($asset['async']) {
            $attributes['async'] = 'async';
        }

        if ($asset['module']) {
            $attributes['type'] = 'module';
        }

        if ($asset['integrity']) {
            $attributes['integrity'] = $asset['integrity'];
        }

        if ($asset['crossorigin']) {
            $attributes['crossorigin'] = $asset['crossorigin'];
        }

        $attributeString = $this->buildAttributeString($attributes);
        return "<script {$attributeString}></script>\n";
    }

    /**
     * Render inline JavaScript
     */
    private function renderInlineJs(string $name, array $asset): string
    {
        $filePath = $this->publicPath . '/' . $asset['path'];
        
        if (!file_exists($filePath)) {
            return "<!-- JS file not found: {$asset['path']} -->\n";
        }

        $js = file_get_contents($filePath);
        
        if ($this->config['minification'] && !$this->config['dev_mode']) {
            $js = $this->minifyJs($js);
        }

        return "<script>\n{$js}\n</script>\n";
    }

    /**
     * Build HTML attribute string
     */
    private function buildAttributeString(array $attributes): string
    {
        $parts = [];
        
        foreach ($attributes as $name => $value) {
            if ($value === true || $value === $name) {
                $parts[] = $name;
            } else {
                $parts[] = $name . '="' . htmlspecialchars($value) . '"';
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Minify CSS
     */
    private function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // Remove unnecessary spaces
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': ', ' :'], [';', '{', '{', '}', '}', ':', ':'], $css);
        
        return trim($css);
    }

    /**
     * Minify JavaScript (basic)
     */
    private function minifyJs(string $js): string
    {
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove extra whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }

    /**
     * Compile assets (build process)
     */
    public function compile(): array
    {
        $results = [];
        
        foreach ($this->assets as $name => $asset) {
            if ($asset['external']) {
                continue;
            }

            $sourcePath = $this->publicPath . '/' . $asset['path'];
            
            if (!file_exists($sourcePath)) {
                $results[$name] = ['status' => 'error', 'message' => 'Source file not found'];
                continue;
            }

            $content = file_get_contents($sourcePath);
            $compiled = $content;

            // Apply transformations
            if ($this->config['minification']) {
                if ($asset['type'] === 'css') {
                    $compiled = $this->minifyCss($compiled);
                } elseif ($asset['type'] === 'js') {
                    $compiled = $this->minifyJs($compiled);
                }
            }

            // Generate versioned filename
            $hash = substr(md5($compiled), 0, 8);
            $pathInfo = pathinfo($asset['path']);
            $versionedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.' . $hash . '.' . $pathInfo['extension'];
            $versionedPath = ltrim($versionedPath, './');

            // Write compiled file
            $outputPath = $this->publicPath . '/' . $versionedPath;
            $outputDir = dirname($outputPath);
            
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            file_put_contents($outputPath, $compiled);

            // Compress if enabled
            if ($this->config['compression'] && function_exists('gzencode')) {
                file_put_contents($outputPath . '.gz', gzencode($compiled, 9));
            }

            $results[$name] = [
                'status' => 'success',
                'original_path' => $asset['path'],
                'versioned_path' => $versionedPath,
                'original_size' => strlen($content),
                'compiled_size' => strlen($compiled),
                'compression_ratio' => round((1 - strlen($compiled) / strlen($content)) * 100, 2)
            ];
        }

        // Update manifest
        $this->updateManifest($results);

        return $results;
    }

    /**
     * Update asset manifest
     */
    private function updateManifest(array $results): void
    {
        $manifest = [];
        
        foreach ($results as $name => $result) {
            if ($result['status'] === 'success') {
                $manifest[$result['original_path']] = $result['versioned_path'];
            }
        }

        $manifestPath = $this->publicPath . '/manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));

        // Update in-memory manifest
        $this->manifests['manifest'] = $manifest;
    }

    /**
     * Clear compiled assets
     */
    public function clearCompiled(): void
    {
        $pattern = $this->publicPath . '/**/*.{css,js}';
        $files = glob($pattern, GLOB_BRACE);
        
        foreach ($files as $file) {
            if ($this->hasVersionInPath($file)) {
                unlink($file);
                
                // Remove gzipped version
                if (file_exists($file . '.gz')) {
                    unlink($file . '.gz');
                }
            }
        }

        // Clear manifest
        $manifestPath = $this->publicPath . '/manifest.json';
        if (file_exists($manifestPath)) {
            unlink($manifestPath);
        }
    }

    /**
     * Get asset statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total_assets' => count($this->assets),
            'css_assets' => count(array_filter($this->assets, fn($asset) => $asset['type'] === 'css')),
            'js_assets' => count(array_filter($this->assets, fn($asset) => $asset['type'] === 'js')),
            'external_assets' => count(array_filter($this->assets, fn($asset) => $asset['external'])),
            'total_size' => 0,
            'compiled_size' => 0,
            'manifests' => count($this->manifests)
        ];

        foreach ($this->assets as $asset) {
            if (!$asset['external']) {
                $filePath = $this->publicPath . '/' . $asset['path'];
                if (file_exists($filePath)) {
                    $stats['total_size'] += filesize($filePath);
                }
            }
        }

        $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);

        return $stats;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Render asset manager UI
     */
    public function renderAssetManager(): string
    {
        $stats = $this->getStats();
        
        return '
        <div class="asset-manager">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Asset Manager</h2>
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-blue-600">' . $stats['total_assets'] . '</div>
                        <div class="text-sm text-gray-600">Total Assets</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-green-600">' . $stats['css_assets'] . '</div>
                        <div class="text-sm text-gray-600">CSS Files</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-yellow-600">' . $stats['js_assets'] . '</div>
                        <div class="text-sm text-gray-600">JS Files</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-purple-600">' . $stats['total_size_formatted'] . '</div>
                        <div class="text-sm text-gray-600">Total Size</div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Asset List -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Registered Assets</h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        ' . $this->renderAssetList() . '
                    </div>
                </div>
                
                <!-- Build Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Build Actions</h3>
                    <div class="space-y-4">
                        <button onclick="compileAssets()" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
                            Compile Assets
                        </button>
                        <button onclick="clearCompiled()" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">
                            Clear Compiled
                        </button>
                        <button onclick="refreshManifest()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                            Refresh Manifest
                        </button>
                    </div>
                    
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-2">Build Settings</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" id="minification" ' . ($this->config['minification'] ? 'checked' : '') . ' class="mr-2">
                                <span class="text-sm">Enable Minification</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" id="compression" ' . ($this->config['compression'] ? 'checked' : '') . ' class="mr-2">
                                <span class="text-sm">Enable Compression</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" id="versioning" ' . ($this->config['versioning'] ? 'checked' : '') . ' class="mr-2">
                                <span class="text-sm">Enable Versioning</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function compileAssets() {
            const settings = {
                minification: document.getElementById("minification").checked,
                compression: document.getElementById("compression").checked,
                versioning: document.getElementById("versioning").checked
            };
            
            fetch("/admin/assets/compile", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(settings)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Assets compiled successfully");
                    location.reload();
                } else {
                    alert("Failed to compile assets: " + data.message);
                }
            })
            .catch(error => alert("Error: " + error.message));
        }
        
        function clearCompiled() {
            if (confirm("Clear all compiled assets?")) {
                fetch("/admin/assets/clear", { method: "DELETE" })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.success ? "Compiled assets cleared" : "Failed to clear assets");
                        location.reload();
                    })
                    .catch(error => alert("Error: " + error.message));
            }
        }
        
        function refreshManifest() {
            fetch("/admin/assets/manifest/refresh", { method: "POST" })
                .then(response => response.json())
                .then(data => {
                    alert(data.success ? "Manifest refreshed" : "Failed to refresh manifest");
                    location.reload();
                })
                .catch(error => alert("Error: " + error.message));
        }
        </script>';
    }

    /**
     * Render asset list
     */
    private function renderAssetList(): string
    {
        $html = '';
        
        foreach ($this->assets as $name => $asset) {
            $typeClass = $asset['type'] === 'css' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800';
            $externalBadge = $asset['external'] ? '<span class="ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">External</span>' : '';
            
            $html .= '
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <span class="px-2 py-1 text-xs font-medium ' . $typeClass . ' rounded">' . strtoupper($asset['type']) . '</span>
                                ' . $externalBadge . '
                                <span class="ml-2 font-medium">' . htmlspecialchars($name) . '</span>
                            </div>
                            <div class="text-sm text-gray-600 mt-1">' . htmlspecialchars($asset['path']) . '</div>
                            <div class="text-xs text-gray-500">Priority: ' . $asset['priority'] . '</div>
                        </div>
                        <div class="text-right">
                            <a href="' . $this->getAssetUrl($name) . '" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                        </div>
                    </div>
                </div>';
        }
        
        return $html;
    }

    /**
     * Preload critical assets
     */
    public function renderPreload(): string
    {
        $criticalAssets = array_filter($this->assets, fn($asset) => $asset['critical']);
        $html = '';
        
        foreach ($criticalAssets as $name => $asset) {
            $url = $this->getAssetUrl($name);
            $as = $asset['type'] === 'css' ? 'style' : 'script';
            
            $html .= "<link rel=\"preload\" href=\"{$url}\" as=\"{$as}\">\n";
        }
        
        return $html;
    }
}
