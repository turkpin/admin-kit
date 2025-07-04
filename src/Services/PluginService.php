<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;

class PluginService
{
    private CacheService $cacheService;
    private array $config;
    private array $plugins;
    private array $hooks;
    private array $events;

    public function __construct(CacheService $cacheService, array $config = [])
    {
        $this->cacheService = $cacheService;
        $this->config = array_merge([
            'plugins_dir' => 'plugins/',
            'auto_load' => true,
            'cache_enabled' => true,
            'cache_ttl' => 3600,
            'dev_mode' => false
        ], $config);
        
        $this->plugins = [];
        $this->hooks = [];
        $this->events = [];
        
        if ($this->config['auto_load']) {
            $this->loadPlugins();
        }
    }

    /**
     * Register a plugin
     */
    public function registerPlugin(string $name, array $plugin): bool
    {
        if (!$this->validatePlugin($plugin)) {
            return false;
        }

        $this->plugins[$name] = array_merge([
            'name' => $name,
            'version' => '1.0.0',
            'description' => '',
            'author' => '',
            'enabled' => true,
            'dependencies' => [],
            'hooks' => [],
            'events' => [],
            'class' => null,
            'instance' => null
        ], $plugin);

        // Register hooks and events
        $this->registerPluginHooks($name, $plugin['hooks'] ?? []);
        $this->registerPluginEvents($name, $plugin['events'] ?? []);

        return true;
    }

    /**
     * Load plugins from directory
     */
    public function loadPlugins(): void
    {
        $pluginsDir = $this->config['plugins_dir'];
        
        if (!is_dir($pluginsDir)) {
            return;
        }

        $cachedPlugins = $this->config['cache_enabled'] 
            ? $this->cacheService->get('plugins_list', fn() => null)
            : null;

        if ($cachedPlugins && !$this->config['dev_mode']) {
            $this->plugins = $cachedPlugins;
            return;
        }

        foreach (glob($pluginsDir . '*/plugin.php') as $pluginFile) {
            $this->loadPlugin($pluginFile);
        }

        if ($this->config['cache_enabled']) {
            $this->cacheService->set('plugins_list', $this->plugins, $this->config['cache_ttl']);
        }
    }

    /**
     * Load individual plugin
     */
    private function loadPlugin(string $pluginFile): void
    {
        if (!file_exists($pluginFile)) {
            return;
        }

        $pluginData = include $pluginFile;
        
        if (!is_array($pluginData) || empty($pluginData['name'])) {
            return;
        }

        $this->registerPlugin($pluginData['name'], $pluginData);
    }

    /**
     * Enable plugin
     */
    public function enablePlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $this->plugins[$name]['enabled'] = true;
        
        // Initialize plugin if it has a class
        if ($this->plugins[$name]['class']) {
            $this->initializePlugin($name);
        }

        $this->clearPluginCache();
        return true;
    }

    /**
     * Disable plugin
     */
    public function disablePlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $this->plugins[$name]['enabled'] = false;
        $this->plugins[$name]['instance'] = null;
        
        $this->clearPluginCache();
        return true;
    }

    /**
     * Initialize plugin instance
     */
    private function initializePlugin(string $name): void
    {
        $plugin = $this->plugins[$name];
        
        if (!$plugin['enabled'] || !$plugin['class'] || $plugin['instance']) {
            return;
        }

        if (class_exists($plugin['class'])) {
            try {
                $this->plugins[$name]['instance'] = new $plugin['class']($this);
            } catch (\Exception $e) {
                error_log("Failed to initialize plugin {$name}: " . $e->getMessage());
            }
        }
    }

    /**
     * Register hook
     */
    public function addHook(string $name, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$name])) {
            $this->hooks[$name] = [];
        }

        $this->hooks[$name][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority
        usort($this->hooks[$name], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Execute hook
     */
    public function executeHook(string $name, $value = null, ...$args)
    {
        if (!isset($this->hooks[$name])) {
            return $value;
        }

        foreach ($this->hooks[$name] as $hook) {
            try {
                $value = call_user_func($hook['callback'], $value, ...$args);
            } catch (\Exception $e) {
                error_log("Hook execution failed for {$name}: " . $e->getMessage());
            }
        }

        return $value;
    }

    /**
     * Register event listener
     */
    public function addEventListener(string $event, callable $callback, int $priority = 10): void
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        $this->events[$event][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority
        usort($this->events[$event], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Dispatch event
     */
    public function dispatchEvent(string $event, array $data = []): array
    {
        $results = [];

        if (!isset($this->events[$event])) {
            return $results;
        }

        foreach ($this->events[$event] as $listener) {
            try {
                $result = call_user_func($listener['callback'], $data);
                if ($result !== null) {
                    $results[] = $result;
                }
            } catch (\Exception $e) {
                error_log("Event listener failed for {$event}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Register plugin hooks
     */
    private function registerPluginHooks(string $pluginName, array $hooks): void
    {
        foreach ($hooks as $hookName => $hookData) {
            if (is_string($hookData)) {
                // Simple callback reference
                $this->addHook($hookName, [$pluginName, $hookData]);
            } elseif (is_array($hookData)) {
                // Hook with priority
                $callback = $hookData['callback'] ?? $hookData[0] ?? null;
                $priority = $hookData['priority'] ?? 10;
                
                if ($callback) {
                    $this->addHook($hookName, [$pluginName, $callback], $priority);
                }
            }
        }
    }

    /**
     * Register plugin events
     */
    private function registerPluginEvents(string $pluginName, array $events): void
    {
        foreach ($events as $eventName => $eventData) {
            if (is_string($eventData)) {
                // Simple callback reference
                $this->addEventListener($eventName, [$pluginName, $eventData]);
            } elseif (is_array($eventData)) {
                // Event with priority
                $callback = $eventData['callback'] ?? $eventData[0] ?? null;
                $priority = $eventData['priority'] ?? 10;
                
                if ($callback) {
                    $this->addEventListener($eventName, [$pluginName, $callback], $priority);
                }
            }
        }
    }

    /**
     * Get plugin info
     */
    public function getPlugin(string $name): ?array
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * Get all plugins
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Get enabled plugins
     */
    public function getEnabledPlugins(): array
    {
        return array_filter($this->plugins, fn($plugin) => $plugin['enabled']);
    }

    /**
     * Check if plugin exists
     */
    public function hasPlugin(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Check if plugin is enabled
     */
    public function isPluginEnabled(string $name): bool
    {
        return $this->plugins[$name]['enabled'] ?? false;
    }

    /**
     * Get plugin statistics
     */
    public function getStats(): array
    {
        $total = count($this->plugins);
        $enabled = count($this->getEnabledPlugins());
        
        return [
            'total' => $total,
            'enabled' => $enabled,
            'disabled' => $total - $enabled,
            'hooks' => count($this->hooks),
            'events' => count($this->events)
        ];
    }

    /**
     * Validate plugin structure
     */
    private function validatePlugin(array $plugin): bool
    {
        $required = ['name'];
        
        foreach ($required as $field) {
            if (!isset($plugin[$field]) || empty($plugin[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear plugin cache
     */
    private function clearPluginCache(): void
    {
        if ($this->config['cache_enabled']) {
            $this->cacheService->delete('plugins_list');
        }
    }

    /**
     * Install plugin from ZIP
     */
    public function installPlugin(string $zipPath): bool
    {
        if (!file_exists($zipPath) || !extension_loaded('zip')) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        $pluginsDir = $this->config['plugins_dir'];
        if (!is_dir($pluginsDir)) {
            mkdir($pluginsDir, 0755, true);
        }

        $extractPath = $pluginsDir . uniqid('plugin_');
        
        try {
            $zip->extractTo($extractPath);
            $zip->close();
            
            // Validate plugin
            $pluginFile = $extractPath . '/plugin.php';
            if (!file_exists($pluginFile)) {
                $this->removeDirectory($extractPath);
                return false;
            }

            $pluginData = include $pluginFile;
            if (!$this->validatePlugin($pluginData)) {
                $this->removeDirectory($extractPath);
                return false;
            }

            // Move to final location
            $finalPath = $pluginsDir . $pluginData['name'];
            if (is_dir($finalPath)) {
                $this->removeDirectory($extractPath);
                return false; // Plugin already exists
            }

            rename($extractPath, $finalPath);
            $this->clearPluginCache();
            
            return true;
        } catch (\Exception $e) {
            if (is_dir($extractPath)) {
                $this->removeDirectory($extractPath);
            }
            return false;
        }
    }

    /**
     * Uninstall plugin
     */
    public function uninstallPlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        // Disable first
        $this->disablePlugin($name);

        // Remove from plugins array
        unset($this->plugins[$name]);

        // Remove directory
        $pluginDir = $this->config['plugins_dir'] . $name;
        if (is_dir($pluginDir)) {
            $this->removeDirectory($pluginDir);
        }

        $this->clearPluginCache();
        return true;
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * Render plugin management UI
     */
    public function renderPluginManager(): string
    {
        $plugins = $this->getPlugins();
        $stats = $this->getStats();
        
        $html = '
        <div class="plugin-manager">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Plugin Manager</h2>
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-blue-600">' . $stats['total'] . '</div>
                        <div class="text-sm text-gray-600">Total Plugins</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-green-600">' . $stats['enabled'] . '</div>
                        <div class="text-sm text-gray-600">Enabled</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-gray-600">' . $stats['disabled'] . '</div>
                        <div class="text-sm text-gray-600">Disabled</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-2xl font-bold text-purple-600">' . $stats['hooks'] . '</div>
                        <div class="text-sm text-gray-600">Active Hooks</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Installed Plugins</h3>
                        <button onclick="showInstallModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            Install Plugin
                        </button>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-200">';
        
        foreach ($plugins as $name => $plugin) {
            $statusClass = $plugin['enabled'] ? 'text-green-600' : 'text-gray-400';
            $statusText = $plugin['enabled'] ? 'Enabled' : 'Disabled';
            $actionButton = $plugin['enabled'] 
                ? '<button onclick="disablePlugin(\'' . $name . '\')" class="text-sm text-red-600 hover:text-red-800">Disable</button>'
                : '<button onclick="enablePlugin(\'' . $name . '\')" class="text-sm text-green-600 hover:text-green-800">Enable</button>';
            
            $html .= '
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <h4 class="text-lg font-medium text-gray-900">' . htmlspecialchars($plugin['name']) . '</h4>
                                <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusClass . ' bg-gray-100">
                                    ' . $statusText . '
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">' . htmlspecialchars($plugin['description']) . '</p>
                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                <span>Version: ' . htmlspecialchars($plugin['version']) . '</span>
                                <span class="mx-2">•</span>
                                <span>Author: ' . htmlspecialchars($plugin['author']) . '</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            ' . $actionButton . '
                            <button onclick="configurePlugin(\'' . $name . '\')" class="text-sm text-indigo-600 hover:text-indigo-800">Configure</button>
                            <button onclick="uninstallPlugin(\'' . $name . '\')" class="text-sm text-red-600 hover:text-red-800">Uninstall</button>
                        </div>
                    </div>
                </div>';
        }
        
        $html .= '
                </div>
            </div>
        </div>
        
        <!-- Install Modal -->
        <div id="install-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Install Plugin</h3>
                <form onsubmit="installPlugin(event)">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Plugin ZIP File</label>
                        <input type="file" name="plugin_zip" accept=".zip" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideInstallModal()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Install
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function enablePlugin(name) {
            fetch(`/admin/plugins/${name}/enable`, { method: "POST" })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Failed to enable plugin: " + data.message);
                    }
                })
                .catch(error => alert("Error: " + error.message));
        }
        
        function disablePlugin(name) {
            if (confirm("Are you sure you want to disable this plugin?")) {
                fetch(`/admin/plugins/${name}/disable`, { method: "POST" })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert("Failed to disable plugin: " + data.message);
                        }
                    })
                    .catch(error => alert("Error: " + error.message));
            }
        }
        
        function uninstallPlugin(name) {
            if (confirm("Are you sure you want to uninstall this plugin? This action cannot be undone.")) {
                fetch(`/admin/plugins/${name}/uninstall`, { method: "DELETE" })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert("Failed to uninstall plugin: " + data.message);
                        }
                    })
                    .catch(error => alert("Error: " + error.message));
            }
        }
        
        function configurePlugin(name) {
            window.location.href = `/admin/plugins/${name}/configure`;
        }
        
        function showInstallModal() {
            document.getElementById("install-modal").classList.remove("hidden");
        }
        
        function hideInstallModal() {
            document.getElementById("install-modal").classList.add("hidden");
        }
        
        function installPlugin(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch("/admin/plugins/install", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert("Failed to install plugin: " + data.message);
                }
            })
            .catch(error => alert("Error: " + error.message));
        }
        </script>';
        
        return $html;
    }

    /**
     * Built-in hooks for common extension points
     */
    public function registerBuiltInHooks(): void
    {
        // Form field hooks
        $this->addHook('form_field_before_render', fn($field) => $field);
        $this->addHook('form_field_after_render', fn($html) => $html);
        
        // CRUD hooks
        $this->addHook('crud_before_create', fn($data) => $data);
        $this->addHook('crud_after_create', fn($entity) => $entity);
        $this->addHook('crud_before_update', fn($data) => $data);
        $this->addHook('crud_after_update', fn($entity) => $entity);
        $this->addHook('crud_before_delete', fn($entity) => $entity);
        $this->addHook('crud_after_delete', fn($id) => $id);
        
        // Menu hooks
        $this->addHook('menu_items', fn($items) => $items);
        $this->addHook('admin_menu_render', fn($html) => $html);
        
        // Dashboard hooks
        $this->addHook('dashboard_widgets', fn($widgets) => $widgets);
        $this->addHook('dashboard_stats', fn($stats) => $stats);
        
        // Authentication hooks
        $this->addHook('auth_before_login', fn($credentials) => $credentials);
        $this->addHook('auth_after_login', fn($user) => $user);
        $this->addHook('auth_before_logout', fn($user) => $user);
        
        // Built-in events
        $this->addEventListener('user_created', fn($data) => null);
        $this->addEventListener('user_updated', fn($data) => null);
        $this->addEventListener('user_deleted', fn($data) => null);
        $this->addEventListener('plugin_activated', fn($data) => null);
        $this->addEventListener('plugin_deactivated', fn($data) => null);
    }
}
