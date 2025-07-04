<?php

declare(strict_types=1);

if (!function_exists('adminkit_path')) {
    /**
     * Get the path to AdminKit package directory
     */
    function adminkit_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__);
        return $path ? $basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $basePath;
    }
}

if (!function_exists('adminkit_asset')) {
    /**
     * Get AdminKit asset URL
     */
    function adminkit_asset(string $path): string
    {
        return '/vendor/turkpin/admin-kit/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('adminkit_template')) {
    /**
     * Get AdminKit template path
     */
    function adminkit_template(string $template): string
    {
        return adminkit_path('templates/' . ltrim($template, '/'));
    }
}

if (!function_exists('adminkit_config')) {
    /**
     * Get AdminKit configuration path
     */
    function adminkit_config(string $file = ''): string
    {
        return adminkit_path('config/' . ltrim($file, '/'));
    }
}

if (!function_exists('adminkit_migration')) {
    /**
     * Get AdminKit migration path
     */
    function adminkit_migration(string $file = ''): string
    {
        return adminkit_path('migrations/' . ltrim($file, '/'));
    }
}

if (!function_exists('adminkit_version')) {
    /**
     * Get AdminKit version
     */
    function adminkit_version(): string
    {
        static $version = null;
        
        if ($version === null) {
            $composerPath = adminkit_path('composer.json');
            if (file_exists($composerPath)) {
                $composer = json_decode(file_get_contents($composerPath), true);
                $version = $composer['version'] ?? '1.0.0';
            } else {
                $version = '1.0.0';
            }
        }
        
        return $version;
    }
}

if (!function_exists('adminkit_translate')) {
    /**
     * Translate text using AdminKit translations
     */
    function adminkit_translate(string $key, array $parameters = [], ?string $locale = null): string
    {
        static $translations = [];
        
        $locale = $locale ?? 'tr';
        
        if (!isset($translations[$locale])) {
            $translationFile = adminkit_path("src/Translations/{$locale}.php");
            if (file_exists($translationFile)) {
                $translations[$locale] = require $translationFile;
            } else {
                $translations[$locale] = [];
            }
        }
        
        $text = $translations[$locale][$key] ?? $key;
        
        // Replace parameters
        foreach ($parameters as $param => $value) {
            $text = str_replace(':' . $param, (string) $value, $text);
        }
        
        return $text;
    }
}

if (!function_exists('adminkit_env')) {
    /**
     * Get environment variable with AdminKit prefix
     */
    function adminkit_env(string $key, mixed $default = null): mixed
    {
        return $_ENV['ADMINKIT_' . $key] ?? $_ENV[$key] ?? $default;
    }
}

if (!function_exists('adminkit_cache_key')) {
    /**
     * Generate AdminKit cache key
     */
    function adminkit_cache_key(string $key): string
    {
        return 'adminkit:' . $key;
    }
}
