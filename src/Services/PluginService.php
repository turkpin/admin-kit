<?php

declare(strict_types=1);

namespace AdminKit\Services;

use AdminKit\AdminKit;
use AdminKit\Interfaces\PluginInterface;

class PluginService
{
    private AdminKit $adminKit;
    private array $plugins = [];
    private array $hooks = [];
    private string $pluginPath;
    private bool $enabled;

    public function __construct(AdminKit $adminKit, string $pluginPath = 'plugins', bool $enabled = true)
    {
        $this->adminKit = $adminKit;
        $this->pluginPath = $pluginPath;
        $this->enabled = $enabled;
        
        if ($this->enabled) {
            $this->initializePluginSystem();
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): self
    {
        $this->enabled = true;
        $this->initializePluginSystem();
        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;
        $this->plugins = [];
        $this->hooks = [];
        return $this;
    }

    /**
     * Register a plugin
     */
    public function registerPlugin(PluginInterface $plugin): self
    {
        if (!$this->enabled) {
            return $this;
        }

        $name = $plugin->getName();
        $this->plugins[$name] = $plugin;
        
        // Initialize the plugin
        $plugin->initialize($this->adminKit);
        
        return $this;
    }

    /**
     * Load plugin from directory
     */
    public function loadPlugin(string $pluginDir): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $manifestFile = $pluginDir . '/plugin.json';
        if (!file_exists($manifestFile)) {
            return false;
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        if (!$manifest) {
            return false;
        }

        $mainFile = $pluginDir . '/' . ($manifest['main'] ?? 'plugin.php');
        if (!file_exists($mainFile)) {
            return false;
        }

        // Load the plugin file
        require_once $mainFile;

        // Try to instantiate the plugin class
        $className = $manifest['class'] ?? null;
        if ($className && class_exists($className)) {
            $plugin = new $className($manifest);
            if ($plugin instanceof PluginInterface) {
                $this->registerPlugin($plugin);
                return true;
            }
        }

        return false;
    }

    /**
     * Load all plugins from plugin directory
     */
    public function loadAllPlugins(): int
    {
        if (!$this->enabled || !is_dir($this->pluginPath)) {
            return 0;
        }

        $loaded = 0;
        $pluginDirs = glob($this->pluginPath . '/*', GLOB_ONLYDIR);
        
        foreach ($pluginDirs as $pluginDir) {
            if ($this->loadPlugin($pluginDir)) {
                $loaded++;
            }
        }

        return $loaded;
    }

    /**
     * Get registered plugins
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Get plugin by name
     */
    public function getPlugin(string $name): ?PluginInterface
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * Check if plugin is loaded
     */
    public function hasPlugin(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Enable a plugin
     */
    public function enablePlugin(string $name): bool
    {
        $plugin = $this->getPlugin($name);
        if ($plugin) {
            $plugin->enable();
            return true;
        }
        return false;
    }

    /**
     * Disable a plugin
     */
    public function disablePlugin(string $name): bool
    {
        $plugin = $this->getPlugin($name);
        if ($plugin) {
            $plugin->disable();
            return true;
        }
        return false;
    }

    /**
     * Unload a plugin
     */
    public function unloadPlugin(string $name): bool
    {
        $plugin = $this->getPlugin($name);
        if ($plugin) {
            $plugin->cleanup();
            unset($this->plugins[$name]);
            return true;
        }
        return false;
    }

    /**
     * Add a hook
     */
    public function addHook(string $hookName, callable $callback, int $priority = 10): self
    {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->hooks[$hookName], function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return $this;
    }

    /**
     * Execute a hook
     */
    public function executeHook(string $hookName, array $args = []): array
    {
        $results = [];
        
        if (!isset($this->hooks[$hookName])) {
            return $results;
        }

        foreach ($this->hooks[$hookName] as $hook) {
            try {
                $result = call_user_func_array($hook['callback'], $args);
                $results[] = $result;
            } catch (\Throwable $e) {
                // Log error but continue
                error_log("Plugin hook error: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get plugin manifest
     */
    public function getPluginManifest(string $name): ?array
    {
        $plugin = $this->getPlugin($name);
        if ($plugin) {
            return $plugin->getManifest();
        }
        return null;
    }

    /**
     * Create plugin skeleton
     */
    public function createPluginSkeleton(string $name, array $config = []): bool
    {
        $pluginDir = $this->pluginPath . '/' . $name;
        
        if (is_dir($pluginDir)) {
            return false; // Plugin already exists
        }

        // Create plugin directory
        if (!mkdir($pluginDir, 0755, true)) {
            return false;
        }

        // Create manifest
        $manifest = array_merge([
            'name' => $name,
            'version' => '1.0.0',
            'description' => 'AdminKit plugin',
            'author' => 'Plugin Author',
            'main' => 'plugin.php',
            'class' => ucfirst($name) . 'Plugin',
            'dependencies' => [],
            'adminkit_version' => '>=1.0.7',
        ], $config);

        file_put_contents($pluginDir . '/plugin.json', json_encode($manifest, JSON_PRETTY_PRINT));

        // Create main plugin file
        $className = $manifest['class'];
        $pluginCode = <<<PHP
<?php

declare(strict_types=1);

use AdminKit\AdminKit;
use AdminKit\Interfaces\PluginInterface;

class {$className} implements PluginInterface
{
    private array \$manifest;
    private bool \$enabled = false;

    public function __construct(array \$manifest)
    {
        \$this->manifest = \$manifest;
    }

    public function getName(): string
    {
        return \$this->manifest['name'];
    }

    public function getVersion(): string
    {
        return \$this->manifest['version'];
    }

    public function getDescription(): string
    {
        return \$this->manifest['description'];
    }

    public function getManifest(): array
    {
        return \$this->manifest;
    }

    public function initialize(AdminKit \$adminKit): void
    {
        // Initialize plugin
        // Add hooks, routes, services, etc.
        
        // Example: Add a custom route
        // \$adminKit->getApp()->get('/plugin/{$name}', [\$this, 'handleRoute']);
        
        // Example: Add a dashboard widget
        // \$adminKit->addDashboardWidget('{$name}_widget', [
        //     'title' => '{$name} Widget',
        //     'type' => 'custom',
        //     'value' => 'Plugin data',
        // ]);
    }

    public function enable(): void
    {
        \$this->enabled = true;
        // Plugin-specific enable logic
    }

    public function disable(): void
    {
        \$this->enabled = false;
        // Plugin-specific disable logic
    }

    public function isEnabled(): bool
    {
        return \$this->enabled;
    }

    public function cleanup(): void
    {
        // Cleanup when plugin is unloaded
    }

    public function handleRoute(\$request, \$response): \$response
    {
        \$response->getBody()->write('Hello from {$name} plugin!');
        return \$response;
    }
}
PHP;

        file_put_contents($pluginDir . '/plugin.php', $pluginCode);

        // Create README
        $readme = <<<MD
# {$name} Plugin

{$manifest['description']}

## Installation

Copy this plugin to the AdminKit plugins directory and it will be automatically loaded.

## Configuration

Edit the plugin.json file to configure the plugin.

## Usage

This plugin adds custom functionality to AdminKit.

## Author

{$manifest['author']}

## Version

{$manifest['version']}
MD;

        file_put_contents($pluginDir . '/README.md', $readme);

        return true;
    }

    /**
     * Get plugin statistics
     */
    public function getStats(): array
    {
        $enabled = 0;
        $disabled = 0;
        
        foreach ($this->plugins as $plugin) {
            if ($plugin->isEnabled()) {
                $enabled++;
            } else {
                $disabled++;
            }
        }

        return [
            'total' => count($this->plugins),
            'enabled' => $enabled,
            'disabled' => $disabled,
            'hooks' => count($this->hooks),
        ];
    }

    /**
     * Initialize plugin system
     */
    private function initializePluginSystem(): void
    {
        // Create plugin directory if it doesn't exist
        if (!is_dir($this->pluginPath)) {
            mkdir($this->pluginPath, 0755, true);
        }

        // Add core hooks
        $this->addCoreHooks();
    }

    /**
     * Add core hooks
     */
    private function addCoreHooks(): void
    {
        // Add hooks that plugins can use
        $coreHooks = [
            'adminkit.init',
            'adminkit.route.before',
            'adminkit.route.after',
            'adminkit.entity.before_create',
            'adminkit.entity.after_create',
            'adminkit.entity.before_update',
            'adminkit.entity.after_update',
            'adminkit.entity.before_delete',
            'adminkit.entity.after_delete',
            'adminkit.dashboard.widgets',
            'adminkit.menu.items',
        ];

        foreach ($coreHooks as $hook) {
            if (!isset($this->hooks[$hook])) {
                $this->hooks[$hook] = [];
            }
        }
    }
}
