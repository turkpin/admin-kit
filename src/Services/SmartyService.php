<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Smarty;

class SmartyService
{
    private Smarty $smarty;
    private array $config;

    public function __construct(Smarty $smarty, array $config)
    {
        $this->smarty = $smarty;
        $this->config = $config;
        $this->setupSmarty();
    }

    private function setupSmarty(): void
    {
        // Template dizinlerini ayarla
        $templatePath = $this->config['template_path'] ?? __DIR__ . '/../../templates';
        $this->smarty->setTemplateDir($templatePath);
        
        // Compile ve cache dizinlerini ayarla
        $compileDir = sys_get_temp_dir() . '/adminkit_compile';
        $cacheDir = sys_get_temp_dir() . '/adminkit_cache';
        
        if (!is_dir($compileDir)) {
            mkdir($compileDir, 0755, true);
        }
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $this->smarty->setCompileDir($compileDir);
        $this->smarty->setCacheDir($cacheDir);
        
        // Cache'i devre dışı bırak (development için)
        $this->smarty->setCaching(Smarty::CACHING_OFF);
        
        // Global değişkenleri ata
        $this->assignGlobalVariables();
        
        // Custom modifier'ları kaydet
        $this->registerModifiers();
        
        // Custom function'ları kaydet
        $this->registerFunctions();
    }

    private function assignGlobalVariables(): void
    {
        $this->smarty->assign([
            'config' => $this->config,
            'brand_name' => $this->config['brand_name'] ?? 'AdminKit',
            'assets_path' => $this->config['assets_path'] ?? '/assets/admin',
            'route_prefix' => $this->config['route_prefix'] ?? '/admin',
            'date_format' => $this->config['date_format'] ?? 'Y-m-d H:i:s',
            'locale' => $this->config['locale'] ?? 'tr',
            'theme' => $this->config['theme'] ?? 'default',
        ]);
    }

    private function registerModifiers(): void
    {
        // Date formatting modifier
        $this->smarty->registerPlugin('modifier', 'date_format', function($date, $format = null) {
            if (!$date) return '';
            
            $format = $format ?: $this->config['date_format'] ?? 'Y-m-d H:i:s';
            
            if ($date instanceof \DateTime) {
                return $date->format($format);
            }
            
            if (is_string($date)) {
                $dateTime = new \DateTime($date);
                return $dateTime->format($format);
            }
            
            return '';
        });

        // Truncate modifier
        $this->smarty->registerPlugin('modifier', 'truncate', function($string, $length = 100, $suffix = '...') {
            if (strlen($string) <= $length) {
                return $string;
            }
            
            return substr($string, 0, $length) . $suffix;
        });

        // Money format modifier
        $this->smarty->registerPlugin('modifier', 'money', function($amount, $currency = 'TL') {
            return number_format($amount, 2, ',', '.') . ' ' . $currency;
        });

        // Boolean to text modifier
        $this->smarty->registerPlugin('modifier', 'bool_text', function($value, $trueText = 'Evet', $falseText = 'Hayır') {
            return $value ? $trueText : $falseText;
        });

        // Escape HTML modifier
        $this->smarty->registerPlugin('modifier', 'escape_html', function($string) {
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        });
    }

    private function registerFunctions(): void
    {
        // URL generator function
        $this->smarty->registerPlugin('function', 'url', function($params) {
            $route = $params['route'] ?? '';
            $prefix = $this->config['route_prefix'] ?? '/admin';
            
            if (strpos($route, '/') === 0) {
                return $route;
            }
            
            return $prefix . '/' . ltrim($route, '/');
        });

        // Asset URL generator function
        $this->smarty->registerPlugin('function', 'asset', function($params) {
            $path = $params['path'] ?? '';
            $assetsPath = $this->config['assets_path'] ?? '/assets/admin';
            
            return $assetsPath . '/' . ltrim($path, '/');
        });

        // Flash message function
        $this->smarty->registerPlugin('function', 'flash', function($params) {
            $type = $params['type'] ?? 'info';
            $message = $params['message'] ?? '';
            
            if (!$message) {
                return '';
            }
            
            $classes = [
                'success' => 'bg-green-100 border-green-400 text-green-700',
                'error' => 'bg-red-100 border-red-400 text-red-700',
                'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
                'info' => 'bg-blue-100 border-blue-400 text-blue-700',
            ];
            
            $class = $classes[$type] ?? $classes['info'];
            
            return '<div class="border px-4 py-3 rounded mb-4 ' . $class . '">' . 
                   htmlspecialchars($message) . '</div>';
        });

        // Icon function
        $this->smarty->registerPlugin('function', 'icon', function($params) {
            $name = $params['name'] ?? 'question';
            $class = $params['class'] ?? 'w-5 h-5';
            
            // Heroicons'dan basit SVG'ler
            $icons = [
                'user' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
                'users' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>',
                'home' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>',
                'edit' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                'delete' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                'plus' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>',
            ];
            
            return $icons[$name] ?? $icons['question'] ?? '';
        });
    }

    public function render(string $template, array $variables = []): string
    {
        // Değişkenleri Smarty'ye ata
        foreach ($variables as $key => $value) {
            $this->smarty->assign($key, $value);
        }
        
        return $this->smarty->fetch($template);
    }

    public function assign(string $key, mixed $value): void
    {
        $this->smarty->assign($key, $value);
    }

    public function assignMultiple(array $variables): void
    {
        foreach ($variables as $key => $value) {
            $this->smarty->assign($key, $value);
        }
    }

    public function getSmarty(): Smarty
    {
        return $this->smarty;
    }
}
