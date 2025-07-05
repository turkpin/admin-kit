#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * AdminKit Asset Compilation System
 * 
 * Compiles, minifies and optimizes CSS/JS assets
 */

// Prevent running from web
if (php_sapi_name() !== 'cli') {
    exit('This script can only be run from the command line.');
}

// Define root directory
define('ADMINKIT_ROOT', dirname(__DIR__));

// Default configuration
$srcDir = ADMINKIT_ROOT . '/assets/src';
$distDir = ADMINKIT_ROOT . '/assets/dist';
$publicDir = ADMINKIT_ROOT . '/public/assets';
$minify = false;
$watch = false;
$verbose = false;

// Parse command line arguments
$options = getopt('s:d:p:mvw', ['src:', 'dist:', 'public:', 'minify', 'verbose', 'watch', 'help', 'version']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

if (isset($options['version'])) {
    echo "AdminKit Asset Compiler v1.0.7\n";
    exit(0);
}

// Override defaults with command line options
$srcDir = $options['s'] ?? $options['src'] ?? $srcDir;
$distDir = $options['d'] ?? $options['dist'] ?? $distDir;
$publicDir = $options['p'] ?? $options['public'] ?? $publicDir;
$minify = isset($options['m']) || isset($options['minify']);
$verbose = isset($options['v']) || isset($options['verbose']);
$watch = isset($options['w']) || isset($options['watch']);

// Create directories if they don't exist
$dirs = [$srcDir, $distDir, $publicDir];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            echo "Error: Could not create directory '$dir'\n";
            exit(1);
        }
        if ($verbose) echo "Created directory: $dir\n";
    }
}

echo "\nAdminKit Asset Compiler\n";
echo "======================\n";
echo "Source:  $srcDir\n";
echo "Dist:    $distDir\n";
echo "Public:  $publicDir\n";
echo "Minify:  " . ($minify ? 'Yes' : 'No') . "\n";
echo "Watch:   " . ($watch ? 'Yes' : 'No') . "\n";
echo "\n";

// Asset compiler class
class AssetCompiler
{
    private string $srcDir;
    private string $distDir;
    private string $publicDir;
    private bool $minify;
    private bool $verbose;

    public function __construct(string $srcDir, string $distDir, string $publicDir, bool $minify, bool $verbose)
    {
        $this->srcDir = $srcDir;
        $this->distDir = $distDir;
        $this->publicDir = $publicDir;
        $this->minify = $minify;
        $this->verbose = $verbose;
    }

    public function compile(): void
    {
        $this->log("Starting compilation...");
        
        $this->compileCSS();
        $this->compileJS();
        $this->copyImages();
        $this->copyFonts();
        $this->generateManifest();
        
        $this->log("Compilation complete!");
    }

    private function compileCSS(): void
    {
        $this->log("Compiling CSS files...");
        
        $cssFiles = $this->findFiles($this->srcDir, '*.css');
        $scssFiles = $this->findFiles($this->srcDir, '*.scss');
        
        // Compile SCSS files if any
        foreach ($scssFiles as $file) {
            $this->compileSCSS($file);
        }
        
        // Process CSS files
        foreach ($cssFiles as $file) {
            $this->processCSS($file);
        }
        
        // Create combined CSS file
        $this->createCombinedCSS();
    }

    private function compileJS(): void
    {
        $this->log("Compiling JavaScript files...");
        
        $jsFiles = $this->findFiles($this->srcDir, '*.js');
        
        foreach ($jsFiles as $file) {
            $this->processJS($file);
        }
        
        // Create combined JS file
        $this->createCombinedJS();
    }

    private function copyImages(): void
    {
        $this->log("Copying images...");
        
        $imageDir = $this->srcDir . '/images';
        if (is_dir($imageDir)) {
            $this->copyDirectory($imageDir, $this->publicDir . '/images');
        }
    }

    private function copyFonts(): void
    {
        $this->log("Copying fonts...");
        
        $fontDir = $this->srcDir . '/fonts';
        if (is_dir($fontDir)) {
            $this->copyDirectory($fontDir, $this->publicDir . '/fonts');
        }
    }

    private function compileSCSS(string $file): void
    {
        // Simple SCSS compilation (basic variable replacement)
        $content = file_get_contents($file);
        
        // Basic variable processing
        $variables = [];
        if (preg_match_all('/\$([a-zA-Z0-9_-]+):\s*([^;]+);/', $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $variables['$' . $matches[1][$i]] = trim($matches[2][$i]);
            }
        }
        
        // Replace variables
        foreach ($variables as $var => $value) {
            $content = str_replace($var, $value, $content);
        }
        
        // Remove variable definitions
        $content = preg_replace('/\$[a-zA-Z0-9_-]+:\s*[^;]+;\s*\n?/', '', $content);
        
        $outputFile = $this->distDir . '/' . basename($file, '.scss') . '.css';
        file_put_contents($outputFile, $content);
        
        $this->log("Compiled SCSS: " . basename($file));
    }

    private function processCSS(string $file): void
    {
        $content = file_get_contents($file);
        
        if ($this->minify) {
            $content = $this->minifyCSS($content);
        }
        
        $outputFile = $this->distDir . '/' . basename($file);
        file_put_contents($outputFile, $content);
        
        $this->log("Processed CSS: " . basename($file));
    }

    private function processJS(string $file): void
    {
        $content = file_get_contents($file);
        
        if ($this->minify) {
            $content = $this->minifyJS($content);
        }
        
        $outputFile = $this->distDir . '/' . basename($file);
        file_put_contents($outputFile, $content);
        
        $this->log("Processed JS: " . basename($file));
    }

    private function createCombinedCSS(): void
    {
        $cssFiles = glob($this->distDir . '/*.css');
        $combined = '';
        
        foreach ($cssFiles as $file) {
            $combined .= "/* " . basename($file) . " */\n";
            $combined .= file_get_contents($file) . "\n\n";
        }
        
        if ($combined) {
            $outputFile = $this->publicDir . '/app.css';
            file_put_contents($outputFile, $combined);
            $this->log("Created combined CSS: app.css");
        }
    }

    private function createCombinedJS(): void
    {
        $jsFiles = glob($this->distDir . '/*.js');
        $combined = '';
        
        foreach ($jsFiles as $file) {
            $combined .= "/* " . basename($file) . " */\n";
            $combined .= file_get_contents($file) . "\n\n";
        }
        
        if ($combined) {
            $outputFile = $this->publicDir . '/app.js';
            file_put_contents($outputFile, $combined);
            $this->log("Created combined JS: app.js");
        }
    }

    private function minifyCSS(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // Remove unnecessary spaces
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/;\s*/', ';', $css);
        $css = preg_replace('/,\s*/', ',', $css);
        
        return trim($css);
    }

    private function minifyJS(string $js): string
    {
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove extra whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove unnecessary spaces around operators
        $js = preg_replace('/\s*([=+\-*\/{}();,:])\s*/', '$1', $js);
        
        return trim($js);
    }

    private function generateManifest(): void
    {
        $manifest = [
            'version' => '1.0.7',
            'timestamp' => date('Y-m-d H:i:s'),
            'files' => [],
        ];
        
        $files = array_merge(
            glob($this->publicDir . '/*.css'),
            glob($this->publicDir . '/*.js'),
            glob($this->publicDir . '/images/*'),
            glob($this->publicDir . '/fonts/*')
        );
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $relativePath = str_replace($this->publicDir . '/', '', $file);
                $manifest['files'][$relativePath] = [
                    'size' => filesize($file),
                    'hash' => md5_file($file),
                    'modified' => date('Y-m-d H:i:s', filemtime($file)),
                ];
            }
        }
        
        $manifestFile = $this->publicDir . '/manifest.json';
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
        
        $this->log("Generated manifest.json");
    }

    private function findFiles(string $dir, string $pattern): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        
        return glob($dir . '/' . $pattern) ?: [];
    }

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            return;
        }
        
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;
            
            if (is_dir($srcFile)) {
                $this->copyDirectory($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
                $this->log("Copied: $file");
            }
        }
    }

    private function log(string $message): void
    {
        if ($this->verbose) {
            echo "[" . date('H:i:s') . "] $message\n";
        }
    }
}

// Create compiler instance
$compiler = new AssetCompiler($srcDir, $distDir, $publicDir, $minify, $verbose);

if ($watch) {
    echo "Watching for changes... Press Ctrl+C to stop\n\n";
    
    $lastModified = 0;
    
    while (true) {
        $currentModified = getDirectoryModifiedTime($srcDir);
        
        if ($currentModified > $lastModified) {
            $compiler->compile();
            $lastModified = $currentModified;
        }
        
        sleep(1);
    }
} else {
    $compiler->compile();
}

function getDirectoryModifiedTime(string $dir): int
{
    if (!is_dir($dir)) {
        return 0;
    }
    
    $latestTime = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $latestTime = max($latestTime, $file->getMTime());
        }
    }
    
    return $latestTime;
}

function showHelp(): void
{
    echo <<<HELP
AdminKit Asset Compiler

USAGE:
    php bin/assets.php [OPTIONS]

OPTIONS:
    -s, --src=PATH       Source directory (default: ./assets/src)
    -d, --dist=PATH      Distribution directory (default: ./assets/dist)
    -p, --public=PATH    Public directory (default: ./public/assets)
    -m, --minify         Minify CSS and JavaScript files
    -v, --verbose        Show detailed output
    -w, --watch          Watch for changes and recompile
    --help               Show this help message
    --version            Show version information

EXAMPLES:
    php bin/assets.php --minify --verbose
    php bin/assets.php --watch
    php bin/assets.php -mvw

FEATURES:
    - CSS compilation and minification
    - Basic SCSS support (variables only)
    - JavaScript minification
    - Asset combination
    - Image and font copying
    - Manifest generation
    - Watch mode for development

HELP;
}
