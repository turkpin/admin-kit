<?php

declare(strict_types=1);

namespace AdminKit\Services;

class ThemeService
{
    private string $themePath;
    private string $activeTheme;
    private array $themes = [];
    private array $config;

    public function __construct(string $themePath = 'themes', array $config = [])
    {
        $this->themePath = $themePath;
        $this->config = array_merge([
            'default_theme' => 'default',
            'cache_enabled' => true,
            'cache_path' => 'var/cache/themes',
            'compile_assets' => true,
        ], $config);
        
        $this->activeTheme = $this->config['default_theme'];
        
        if (!is_dir($this->themePath)) {
            mkdir($this->themePath, 0755, true);
        }
        
        $this->loadThemes();
    }

    /**
     * Get active theme
     */
    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }

    /**
     * Set active theme
     */
    public function setActiveTheme(string $themeName): bool
    {
        if (!$this->hasTheme($themeName)) {
            return false;
        }

        $this->activeTheme = $themeName;
        
        // Clear cache when theme changes
        if ($this->config['cache_enabled']) {
            $this->clearCache();
        }
        
        return true;
    }

    /**
     * Get all available themes
     */
    public function getThemes(): array
    {
        return $this->themes;
    }

    /**
     * Check if theme exists
     */
    public function hasTheme(string $themeName): bool
    {
        return isset($this->themes[$themeName]);
    }

    /**
     * Get theme configuration
     */
    public function getThemeConfig(string $themeName): ?array
    {
        return $this->themes[$themeName] ?? null;
    }

    /**
     * Get active theme configuration
     */
    public function getActiveThemeConfig(): array
    {
        return $this->getThemeConfig($this->activeTheme) ?? [];
    }

    /**
     * Get theme asset path
     */
    public function getAssetPath(string $asset, string $themeName = null): string
    {
        $theme = $themeName ?? $this->activeTheme;
        $themeConfig = $this->getThemeConfig($theme);
        
        if (!$themeConfig) {
            return $asset;
        }

        $assetPath = $this->themePath . '/' . $theme . '/assets/' . $asset;
        
        // Check if asset exists in theme, fallback to default
        if (file_exists($assetPath)) {
            return '/themes/' . $theme . '/assets/' . $asset;
        }

        // Fallback to default theme
        if ($theme !== 'default') {
            return $this->getAssetPath($asset, 'default');
        }

        return $asset;
    }

    /**
     * Get theme template path
     */
    public function getTemplatePath(string $template, string $themeName = null): string
    {
        $theme = $themeName ?? $this->activeTheme;
        $themeConfig = $this->getThemeConfig($theme);
        
        if (!$themeConfig) {
            return $template;
        }

        $templatePath = $this->themePath . '/' . $theme . '/templates/' . $template;
        
        // Check if template exists in theme
        if (file_exists($templatePath)) {
            return $templatePath;
        }

        // Fallback to default theme
        if ($theme !== 'default') {
            return $this->getTemplatePath($template, 'default');
        }

        return $template;
    }

    /**
     * Get theme CSS files
     */
    public function getThemeCSS(string $themeName = null): array
    {
        $theme = $themeName ?? $this->activeTheme;
        $themeConfig = $this->getThemeConfig($theme);
        
        if (!$themeConfig) {
            return [];
        }

        $cssFiles = [];
        $cssPath = $this->themePath . '/' . $theme . '/assets/css';
        
        if (is_dir($cssPath)) {
            $files = glob($cssPath . '/*.css');
            foreach ($files as $file) {
                $cssFiles[] = '/themes/' . $theme . '/assets/css/' . basename($file);
            }
        }

        return $cssFiles;
    }

    /**
     * Get theme JavaScript files
     */
    public function getThemeJS(string $themeName = null): array
    {
        $theme = $themeName ?? $this->activeTheme;
        $themeConfig = $this->getThemeConfig($theme);
        
        if (!$themeConfig) {
            return [];
        }

        $jsFiles = [];
        $jsPath = $this->themePath . '/' . $theme . '/assets/js';
        
        if (is_dir($jsPath)) {
            $files = glob($jsPath . '/*.js');
            foreach ($files as $file) {
                $jsFiles[] = '/themes/' . $theme . '/assets/js/' . basename($file);
            }
        }

        return $jsFiles;
    }

    /**
     * Install theme from directory
     */
    public function installTheme(string $sourceDir): bool
    {
        if (!is_dir($sourceDir)) {
            throw new \InvalidArgumentException("Source directory does not exist: $sourceDir");
        }

        $manifestFile = $sourceDir . '/theme.json';
        if (!file_exists($manifestFile)) {
            throw new \InvalidArgumentException("Theme manifest file not found: $manifestFile");
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        if (!$manifest) {
            throw new \InvalidArgumentException("Invalid theme manifest file");
        }

        $themeName = $manifest['name'] ?? basename($sourceDir);
        $targetDir = $this->themePath . '/' . $themeName;

        // Copy theme files
        $this->copyDirectory($sourceDir, $targetDir);

        // Reload themes
        $this->loadThemes();

        return true;
    }

    /**
     * Uninstall theme
     */
    public function uninstallTheme(string $themeName): bool
    {
        if ($themeName === 'default') {
            throw new \InvalidArgumentException("Cannot uninstall default theme");
        }

        if (!$this->hasTheme($themeName)) {
            return false;
        }

        $themeDir = $this->themePath . '/' . $themeName;
        
        if (is_dir($themeDir)) {
            $this->removeDirectory($themeDir);
        }

        // Switch to default theme if this was active
        if ($this->activeTheme === $themeName) {
            $this->setActiveTheme('default');
        }

        // Reload themes
        $this->loadThemes();

        return true;
    }

    /**
     * Create theme skeleton
     */
    public function createTheme(string $themeName, array $config = []): bool
    {
        $themeDir = $this->themePath . '/' . $themeName;
        
        if (is_dir($themeDir)) {
            throw new \InvalidArgumentException("Theme already exists: $themeName");
        }

        // Create theme directory structure
        $directories = [
            $themeDir,
            $themeDir . '/assets',
            $themeDir . '/assets/css',
            $themeDir . '/assets/js',
            $themeDir . '/assets/images',
            $themeDir . '/templates',
            $themeDir . '/templates/layout',
            $themeDir . '/templates/components',
        ];

        foreach ($directories as $dir) {
            if (!mkdir($dir, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: $dir");
            }
        }

        // Create theme manifest
        $manifest = array_merge([
            'name' => $themeName,
            'version' => '1.0.0',
            'description' => 'Custom AdminKit theme',
            'author' => 'Theme Author',
            'adminkit_version' => '>=1.0.7',
            'parent' => 'default',
            'assets' => [
                'css' => ['theme.css'],
                'js' => ['theme.js'],
            ],
            'colors' => [
                'primary' => '#007bff',
                'secondary' => '#6c757d',
                'success' => '#28a745',
                'danger' => '#dc3545',
                'warning' => '#ffc107',
                'info' => '#17a2b8',
            ],
            'fonts' => [
                'family' => 'Inter, sans-serif',
                'size' => '14px',
            ],
        ], $config);

        file_put_contents($themeDir . '/theme.json', json_encode($manifest, JSON_PRETTY_PRINT));

        // Create basic CSS file
        $css = <<<CSS
/* {$themeName} Theme */
:root {
    --color-primary: {$manifest['colors']['primary']};
    --color-secondary: {$manifest['colors']['secondary']};
    --color-success: {$manifest['colors']['success']};
    --color-danger: {$manifest['colors']['danger']};
    --color-warning: {$manifest['colors']['warning']};
    --color-info: {$manifest['colors']['info']};
    --font-family: {$manifest['fonts']['family']};
    --font-size: {$manifest['fonts']['size']};
}

body {
    font-family: var(--font-family);
    font-size: var(--font-size);
}

.btn-primary {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
}

.text-primary {
    color: var(--color-primary) !important;
}

/* Add your custom styles here */
CSS;

        file_put_contents($themeDir . '/assets/css/theme.css', $css);

        // Create basic JS file
        $js = <<<JS
// {$themeName} Theme JavaScript
(function() {
    'use strict';
    
    // Theme initialization
    document.addEventListener('DOMContentLoaded', function() {
        console.log('{$themeName} theme loaded');
        
        // Add your custom JavaScript here
    });
})();
JS;

        file_put_contents($themeDir . '/assets/js/theme.js', $js);

        // Create README
        $readme = <<<MD
# {$themeName} Theme

{$manifest['description']}

## Installation

Copy this theme to the AdminKit themes directory.

## Customization

Edit the CSS and JavaScript files in the assets directory to customize the theme.

## Files

- `theme.json` - Theme configuration
- `assets/css/theme.css` - Theme styles
- `assets/js/theme.js` - Theme scripts
- `templates/` - Custom templates (optional)

## Author

{$manifest['author']}

## Version

{$manifest['version']}
MD;

        file_put_contents($themeDir . '/README.md', $readme);

        // Reload themes
        $this->loadThemes();

        return true;
    }

    /**
     * Compile theme assets
     */
    public function compileThemeAssets(string $themeName = null): bool
    {
        if (!$this->config['compile_assets']) {
            return true;
        }

        $theme = $themeName ?? $this->activeTheme;
        $themeConfig = $this->getThemeConfig($theme);
        
        if (!$themeConfig) {
            return false;
        }

        $themeDir = $this->themePath . '/' . $theme;
        $assetsDir = $themeDir . '/assets';
        $compiledDir = $themeDir . '/compiled';

        if (!is_dir($compiledDir)) {
            mkdir($compiledDir, 0755, true);
        }

        // Compile CSS
        $this->compileCss($assetsDir, $compiledDir);

        // Compile JavaScript
        $this->compileJs($assetsDir, $compiledDir);

        return true;
    }

    /**
     * Get theme variables for CSS
     */
    public function getThemeVariables(string $themeName = null): array
    {
        $theme = $themeName ?? $this->activeTheme;
        $themeConfig = $this->getThemeConfig($theme);
        
        if (!$themeConfig) {
            return [];
        }

        $variables = [];
        
        // Add color variables
        if (isset($themeConfig['colors'])) {
            foreach ($themeConfig['colors'] as $name => $value) {
                $variables["--color-$name"] = $value;
            }
        }

        // Add font variables
        if (isset($themeConfig['fonts'])) {
            foreach ($themeConfig['fonts'] as $name => $value) {
                $variables["--font-$name"] = $value;
            }
        }

        return $variables;
    }

    /**
     * Clear theme cache
     */
    public function clearCache(): bool
    {
        if (!$this->config['cache_enabled']) {
            return true;
        }

        $cacheDir = $this->config['cache_path'];
        
        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
        }

        return true;
    }

    /**
     * Load all available themes
     */
    private function loadThemes(): void
    {
        $this->themes = [];
        
        if (!is_dir($this->themePath)) {
            return;
        }

        $themeDirs = glob($this->themePath . '/*', GLOB_ONLYDIR);
        
        foreach ($themeDirs as $themeDir) {
            $themeName = basename($themeDir);
            $manifestFile = $themeDir . '/theme.json';
            
            if (file_exists($manifestFile)) {
                $manifest = json_decode(file_get_contents($manifestFile), true);
                if ($manifest) {
                    $this->themes[$themeName] = array_merge($manifest, [
                        'path' => $themeDir,
                        'assets_path' => $themeDir . '/assets',
                        'templates_path' => $themeDir . '/templates',
                    ]);
                }
            }
        }

        // Ensure default theme exists
        if (!isset($this->themes['default'])) {
            $this->createDefaultTheme();
        }
    }

    /**
     * Create default theme
     */
    private function createDefaultTheme(): void
    {
        $this->createTheme('default', [
            'name' => 'default',
            'description' => 'Default AdminKit theme',
            'author' => 'AdminKit Team',
        ]);
    }

    /**
     * Compile CSS files
     */
    private function compileCss(string $assetsDir, string $compiledDir): void
    {
        $cssDir = $assetsDir . '/css';
        if (!is_dir($cssDir)) {
            return;
        }

        $cssFiles = glob($cssDir . '/*.css');
        $compiled = '';

        foreach ($cssFiles as $cssFile) {
            $compiled .= "/* " . basename($cssFile) . " */\n";
            $compiled .= file_get_contents($cssFile) . "\n\n";
        }

        if ($compiled) {
            file_put_contents($compiledDir . '/theme.css', $compiled);
        }
    }

    /**
     * Compile JavaScript files
     */
    private function compileJs(string $assetsDir, string $compiledDir): void
    {
        $jsDir = $assetsDir . '/js';
        if (!is_dir($jsDir)) {
            return;
        }

        $jsFiles = glob($jsDir . '/*.js');
        $compiled = '';

        foreach ($jsFiles as $jsFile) {
            $compiled .= "/* " . basename($jsFile) . " */\n";
            $compiled .= file_get_contents($jsFile) . "\n\n";
        }

        if ($compiled) {
            file_put_contents($compiledDir . '/theme.js', $compiled);
        }
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . '/' . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Get theme statistics
     */
    public function getStats(): array
    {
        return [
            'total_themes' => count($this->themes),
            'active_theme' => $this->activeTheme,
            'themes' => array_keys($this->themes),
        ];
    }
}
