<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;

class ThemeService
{
    private CacheService $cacheService;
    private array $themes;
    private string $currentTheme;
    private string $defaultTheme;
    private array $config;

    public function __construct(CacheService $cacheService, array $config = [])
    {
        $this->cacheService = $cacheService;
        $this->config = array_merge([
            'default_theme' => 'light',
            'user_preference_enabled' => true,
            'cache_ttl' => 3600,
            'custom_themes_enabled' => true
        ], $config);

        $this->defaultTheme = $this->config['default_theme'];
        $this->currentTheme = $this->defaultTheme;
        $this->registerDefaultThemes();
    }

    /**
     * Register default themes
     */
    private function registerDefaultThemes(): void
    {
        $this->themes = [
            'light' => [
                'name' => 'Light Theme',
                'description' => 'Clean and bright interface',
                'type' => 'built-in',
                'variables' => [
                    // Main colors
                    '--bg-primary' => '#ffffff',
                    '--bg-secondary' => '#f8fafc',
                    '--bg-tertiary' => '#f1f5f9',
                    '--text-primary' => '#1f2937',
                    '--text-secondary' => '#6b7280',
                    '--text-muted' => '#9ca3af',
                    
                    // Brand colors
                    '--brand-primary' => '#3b82f6',
                    '--brand-secondary' => '#6366f1',
                    '--brand-accent' => '#8b5cf6',
                    
                    // Status colors
                    '--success' => '#10b981',
                    '--warning' => '#f59e0b',
                    '--error' => '#ef4444',
                    '--info' => '#3b82f6',
                    
                    // Border and shadows
                    '--border-color' => '#e5e7eb',
                    '--border-light' => '#f3f4f6',
                    '--shadow' => '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
                    '--shadow-md' => '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                    '--shadow-lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
                    
                    // Form elements
                    '--input-bg' => '#ffffff',
                    '--input-border' => '#d1d5db',
                    '--input-focus' => '#3b82f6',
                    
                    // Navigation
                    '--nav-bg' => '#ffffff',
                    '--nav-text' => '#374151',
                    '--nav-hover' => '#f3f4f6',
                    '--nav-active' => '#3b82f6',
                ],
                'css_overrides' => ''
            ],
            
            'dark' => [
                'name' => 'Dark Theme',
                'description' => 'Modern dark interface for low-light environments',
                'type' => 'built-in',
                'variables' => [
                    // Main colors
                    '--bg-primary' => '#111827',
                    '--bg-secondary' => '#1f2937',
                    '--bg-tertiary' => '#374151',
                    '--text-primary' => '#f9fafb',
                    '--text-secondary' => '#d1d5db',
                    '--text-muted' => '#9ca3af',
                    
                    // Brand colors
                    '--brand-primary' => '#60a5fa',
                    '--brand-secondary' => '#818cf8',
                    '--brand-accent' => '#a78bfa',
                    
                    // Status colors
                    '--success' => '#34d399',
                    '--warning' => '#fbbf24',
                    '--error' => '#f87171',
                    '--info' => '#60a5fa',
                    
                    // Border and shadows
                    '--border-color' => '#374151',
                    '--border-light' => '#4b5563',
                    '--shadow' => '0 1px 3px 0 rgba(0, 0, 0, 0.3)',
                    '--shadow-md' => '0 4px 6px -1px rgba(0, 0, 0, 0.3)',
                    '--shadow-lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.3)',
                    
                    // Form elements
                    '--input-bg' => '#374151',
                    '--input-border' => '#4b5563',
                    '--input-focus' => '#60a5fa',
                    
                    // Navigation
                    '--nav-bg' => '#1f2937',
                    '--nav-text' => '#e5e7eb',
                    '--nav-hover' => '#374151',
                    '--nav-active' => '#60a5fa',
                ],
                'css_overrides' => `
                    body { color-scheme: dark; }
                    ::-webkit-scrollbar { background: var(--bg-secondary); }
                    ::-webkit-scrollbar-thumb { background: var(--border-color); }
                `
            ],
            
            'blue' => [
                'name' => 'Blue Theme',
                'description' => 'Professional blue color scheme',
                'type' => 'built-in',
                'variables' => [
                    '--bg-primary' => '#ffffff',
                    '--bg-secondary' => '#eff6ff',
                    '--bg-tertiary' => '#dbeafe',
                    '--text-primary' => '#1e3a8a',
                    '--text-secondary' => '#1d4ed8',
                    '--text-muted' => '#6b7280',
                    '--brand-primary' => '#1d4ed8',
                    '--brand-secondary' => '#2563eb',
                    '--brand-accent' => '#3b82f6',
                    '--nav-bg' => '#1e40af',
                    '--nav-text' => '#ffffff',
                    '--nav-hover' => '#1d4ed8',
                    '--nav-active' => '#60a5fa',
                ],
                'css_overrides' => ''
            ],
            
            'green' => [
                'name' => 'Green Theme',
                'description' => 'Nature-inspired green theme',
                'type' => 'built-in',
                'variables' => [
                    '--bg-primary' => '#ffffff',
                    '--bg-secondary' => '#f0fdf4',
                    '--bg-tertiary' => '#dcfce7',
                    '--text-primary' => '#14532d',
                    '--text-secondary' => '#166534',
                    '--text-muted' => '#6b7280',
                    '--brand-primary' => '#16a34a',
                    '--brand-secondary' => '#15803d',
                    '--brand-accent' => '#22c55e',
                    '--nav-bg' => '#15803d',
                    '--nav-text' => '#ffffff',
                    '--nav-hover' => '#166534',
                    '--nav-active' => '#4ade80',
                ],
                'css_overrides' => ''
            ]
        ];
    }

    /**
     * Get current theme
     */
    public function getCurrentTheme(): string
    {
        return $this->currentTheme;
    }

    /**
     * Set current theme
     */
    public function setTheme(string $themeName): bool
    {
        if (!isset($this->themes[$themeName])) {
            return false;
        }

        $this->currentTheme = $themeName;
        return true;
    }

    /**
     * Get user's preferred theme
     */
    public function getUserTheme(int $userId): string
    {
        if (!$this->config['user_preference_enabled']) {
            return $this->defaultTheme;
        }

        $cacheKey = "user_theme:{$userId}";
        return $this->cacheService->get($cacheKey, fn() => $this->defaultTheme);
    }

    /**
     * Set user's preferred theme
     */
    public function setUserTheme(int $userId, string $themeName): bool
    {
        if (!isset($this->themes[$themeName]) || !$this->config['user_preference_enabled']) {
            return false;
        }

        $cacheKey = "user_theme:{$userId}";
        $this->cacheService->set($cacheKey, $themeName, 86400 * 365); // Store for 1 year
        
        return true;
    }

    /**
     * Get all available themes
     */
    public function getAvailableThemes(): array
    {
        return array_map(function($theme, $key) {
            return [
                'key' => $key,
                'name' => $theme['name'],
                'description' => $theme['description'],
                'type' => $theme['type']
            ];
        }, $this->themes, array_keys($this->themes));
    }

    /**
     * Get theme configuration
     */
    public function getTheme(string $themeName): ?array
    {
        return $this->themes[$themeName] ?? null;
    }

    /**
     * Register custom theme
     */
    public function registerTheme(string $name, array $config): bool
    {
        if (!$this->config['custom_themes_enabled']) {
            return false;
        }

        $this->themes[$name] = array_merge([
            'name' => ucfirst($name),
            'description' => 'Custom theme',
            'type' => 'custom',
            'variables' => [],
            'css_overrides' => ''
        ], $config);

        return true;
    }

    /**
     * Generate CSS for current theme
     */
    public function generateCSS(string $themeName = null): string
    {
        $themeName = $themeName ?: $this->currentTheme;
        $theme = $this->getTheme($themeName);
        
        if (!$theme) {
            $theme = $this->getTheme($this->defaultTheme);
        }

        $css = ":root {\n";
        
        foreach ($theme['variables'] as $variable => $value) {
            $css .= "    {$variable}: {$value};\n";
        }
        
        $css .= "}\n\n";
        
        // Add theme-specific overrides
        if (!empty($theme['css_overrides'])) {
            $css .= $theme['css_overrides'] . "\n";
        }
        
        // Add responsive theme utilities
        $css .= $this->generateThemeUtilities();
        
        return $css;
    }

    /**
     * Generate theme utility classes
     */
    private function generateThemeUtilities(): string
    {
        return "
        /* Theme Utilities */
        .theme-bg-primary { background-color: var(--bg-primary); }
        .theme-bg-secondary { background-color: var(--bg-secondary); }
        .theme-bg-tertiary { background-color: var(--bg-tertiary); }
        
        .theme-text-primary { color: var(--text-primary); }
        .theme-text-secondary { color: var(--text-secondary); }
        .theme-text-muted { color: var(--text-muted); }
        
        .theme-border { border-color: var(--border-color); }
        .theme-border-light { border-color: var(--border-light); }
        
        .theme-shadow { box-shadow: var(--shadow); }
        .theme-shadow-md { box-shadow: var(--shadow-md); }
        .theme-shadow-lg { box-shadow: var(--shadow-lg); }
        
        /* Form theming */
        .theme-input {
            background-color: var(--input-bg);
            border-color: var(--input-border);
            color: var(--text-primary);
        }
        
        .theme-input:focus {
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Navigation theming */
        .theme-nav {
            background-color: var(--nav-bg);
            color: var(--nav-text);
        }
        
        .theme-nav-item:hover {
            background-color: var(--nav-hover);
        }
        
        .theme-nav-item.active {
            background-color: var(--nav-active);
            color: white;
        }
        
        /* Button theming */
        .theme-btn-primary {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
            color: white;
        }
        
        .theme-btn-primary:hover {
            background-color: var(--brand-secondary);
            border-color: var(--brand-secondary);
        }
        
        /* Status colors */
        .theme-success { color: var(--success); }
        .theme-warning { color: var(--warning); }
        .theme-error { color: var(--error); }
        .theme-info { color: var(--info); }
        
        .theme-bg-success { background-color: var(--success); }
        .theme-bg-warning { background-color: var(--warning); }
        .theme-bg-error { background-color: var(--error); }
        .theme-bg-info { background-color: var(--info); }
        
        /* Dark mode specific adjustments */
        [data-theme='dark'] img {
            opacity: 0.9;
        }
        
        [data-theme='dark'] .shadow-lg {
            box-shadow: var(--shadow-lg);
        }
        
        /* Theme transition animations */
        * {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }
        ";
    }

    /**
     * Detect user's preferred color scheme
     */
    public function detectPreferredScheme(): string
    {
        // This would be enhanced with JavaScript to detect actual user preference
        return $this->defaultTheme;
    }

    /**
     * Get theme switch HTML
     */
    public function renderThemeSwitch(int $userId = null): string
    {
        $currentTheme = $userId ? $this->getUserTheme($userId) : $this->currentTheme;
        $themes = $this->getAvailableThemes();
        
        $html = '
        <div class="relative inline-block text-left theme-selector">
            <button type="button" onclick="toggleThemeMenu()" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    id="theme-menu-button">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                </svg>
                <span id="current-theme-name">' . ($this->themes[$currentTheme]['name'] ?? 'Theme') . '</span>
                <svg class="-mr-1 ml-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>

            <div id="theme-menu" class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                <div class="py-1">';

        foreach ($themes as $theme) {
            $isActive = $theme['key'] === $currentTheme;
            $activeClass = $isActive ? 'bg-gray-50 text-gray-900' : 'text-gray-700';
            
            $html .= '
                <button onclick="changeTheme(\'' . $theme['key'] . '\')" 
                        class="' . $activeClass . ' group flex items-center w-full px-4 py-2 text-sm hover:bg-gray-100 hover:text-gray-900">
                    <div class="w-4 h-4 rounded-full mr-3 theme-preview-' . $theme['key'] . '"></div>
                    <div class="text-left">
                        <div class="font-medium">' . $theme['name'] . '</div>
                        <div class="text-xs text-gray-500">' . $theme['description'] . '</div>
                    </div>';
            
            if ($isActive) {
                $html .= '
                    <svg class="ml-auto h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>';
            }
            
            $html .= '</button>';
        }

        $html .= '
                </div>
            </div>
        </div>

        <script>
        function toggleThemeMenu() {
            const menu = document.getElementById("theme-menu");
            const button = document.getElementById("theme-menu-button");
            
            if (menu.classList.contains("hidden")) {
                menu.classList.remove("hidden");
                button.setAttribute("aria-expanded", "true");
            } else {
                menu.classList.add("hidden");
                button.setAttribute("aria-expanded", "false");
            }
        }

        function changeTheme(themeName) {
            // Update UI immediately
            document.documentElement.setAttribute("data-theme", themeName);
            
            // Update current theme name
            const themeNames = {
                "light": "Light Theme",
                "dark": "Dark Theme",
                "blue": "Blue Theme",
                "green": "Green Theme"
            };
            
            document.getElementById("current-theme-name").textContent = themeNames[themeName] || themeName;
            
            // Save preference via AJAX
            fetch("/admin/theme/set", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ theme: themeName })
            }).then(response => {
                if (response.ok) {
                    // Reload CSS
                    updateThemeCSS(themeName);
                }
            }).catch(error => {
                console.error("Theme change failed:", error);
            });
            
            // Close menu
            document.getElementById("theme-menu").classList.add("hidden");
        }

        function updateThemeCSS(themeName) {
            // Remove old theme CSS
            const oldThemeLink = document.getElementById("theme-css");
            if (oldThemeLink) {
                oldThemeLink.remove();
            }
            
            // Add new theme CSS
            const link = document.createElement("link");
            link.id = "theme-css";
            link.rel = "stylesheet";
            link.href = "/admin/theme/" + themeName + "/css";
            document.head.appendChild(link);
        }

        // Initialize theme preview colors
        document.addEventListener("DOMContentLoaded", function() {
            const previews = {
                "light": "#3b82f6",
                "dark": "#60a5fa", 
                "blue": "#1d4ed8",
                "green": "#16a34a"
            };
            
            for (const [theme, color] of Object.entries(previews)) {
                const element = document.querySelector(".theme-preview-" + theme);
                if (element) {
                    element.style.backgroundColor = color;
                }
            }
        });

        // Close menu when clicking outside
        document.addEventListener("click", function(event) {
            const menu = document.getElementById("theme-menu");
            const button = document.getElementById("theme-menu-button");
            
            if (!button.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add("hidden");
                button.setAttribute("aria-expanded", "false");
            }
        });
        </script>';

        return $html;
    }

    /**
     * Get theme as CSS response
     */
    public function getThemeCSSResponse(string $themeName): array
    {
        $css = $this->generateCSS($themeName);
        
        return [
            'content' => $css,
            'headers' => [
                'Content-Type' => 'text/css; charset=utf-8',
                'Cache-Control' => 'public, max-age=' . $this->config['cache_ttl'],
                'ETag' => md5($css)
            ]
        ];
    }

    /**
     * Import theme from file
     */
    public function importTheme(string $themeName, string $filePath): bool
    {
        if (!$this->config['custom_themes_enabled'] || !file_exists($filePath)) {
            return false;
        }

        $themeData = json_decode(file_get_contents($filePath), true);
        
        if (!$themeData || !isset($themeData['variables'])) {
            return false;
        }

        return $this->registerTheme($themeName, $themeData);
    }

    /**
     * Export theme to file
     */
    public function exportTheme(string $themeName, string $filePath): bool
    {
        $theme = $this->getTheme($themeName);
        
        if (!$theme) {
            return false;
        }

        $exportData = json_encode($theme, JSON_PRETTY_PRINT);
        return file_put_contents($filePath, $exportData) !== false;
    }
}
