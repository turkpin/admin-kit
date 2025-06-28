<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

class ConfigService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getNestedValue($this->config, explode('.', $key), $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->setNestedValue($this->config, explode('.', $key), $value);
    }

    public function has(string $key): bool
    {
        return $this->getNestedValue($this->config, explode('.', $key)) !== null;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function merge(array $config): void
    {
        $this->config = array_merge_recursive($this->config, $config);
    }

    private function getNestedValue(array $array, array $keys, mixed $default = null): mixed
    {
        $key = array_shift($keys);
        
        if (!array_key_exists($key, $array)) {
            return $default;
        }

        if (empty($keys)) {
            return $array[$key];
        }

        if (!is_array($array[$key])) {
            return $default;
        }

        return $this->getNestedValue($array[$key], $keys, $default);
    }

    private function setNestedValue(array &$array, array $keys, mixed $value): void
    {
        $key = array_shift($keys);

        if (empty($keys)) {
            $array[$key] = $value;
            return;
        }

        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }

        $this->setNestedValue($array[$key], $keys, $value);
    }

    public function getRoutePrefix(): string
    {
        return $this->get('route_prefix', '/admin');
    }

    public function getTemplatePath(): string
    {
        return $this->get('template_path', __DIR__ . '/../../templates');
    }

    public function getAssetsPath(): string
    {
        return $this->get('assets_path', '/assets/admin');
    }

    public function isAuthRequired(): bool
    {
        return $this->get('auth_required', true);
    }

    public function isRbacEnabled(): bool
    {
        return $this->get('rbac_enabled', true);
    }

    public function getPaginationLimit(): int
    {
        return $this->get('pagination_limit', 20);
    }

    public function getUploadPath(): string
    {
        return $this->get('upload_path', 'uploads/');
    }

    public function getTheme(): string
    {
        return $this->get('theme', 'default');
    }

    public function getBrandName(): string
    {
        return $this->get('brand_name', 'AdminKit');
    }

    public function getDashboardTitle(): string
    {
        return $this->get('dashboard_title', 'Dashboard');
    }

    public function getDateFormat(): string
    {
        return $this->get('date_format', 'Y-m-d H:i:s');
    }

    public function getLocale(): string
    {
        return $this->get('locale', 'tr');
    }

    public function isCsrfProtectionEnabled(): bool
    {
        return $this->get('csrf_protection', true);
    }
}
