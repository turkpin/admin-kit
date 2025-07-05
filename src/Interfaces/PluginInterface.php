<?php

declare(strict_types=1);

namespace AdminKit\Interfaces;

use AdminKit\AdminKit;

interface PluginInterface
{
    /**
     * Get plugin name
     */
    public function getName(): string;

    /**
     * Get plugin version
     */
    public function getVersion(): string;

    /**
     * Get plugin description
     */
    public function getDescription(): string;

    /**
     * Get plugin manifest
     */
    public function getManifest(): array;

    /**
     * Initialize plugin with AdminKit instance
     */
    public function initialize(AdminKit $adminKit): void;

    /**
     * Enable the plugin
     */
    public function enable(): void;

    /**
     * Disable the plugin
     */
    public function disable(): void;

    /**
     * Check if plugin is enabled
     */
    public function isEnabled(): bool;

    /**
     * Cleanup when plugin is unloaded
     */
    public function cleanup(): void;
}
