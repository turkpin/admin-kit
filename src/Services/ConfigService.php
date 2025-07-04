<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

/**
 * AdminKit Configuration Service
 * 
 * Manages application configuration with environment variable support
 */
class ConfigService
{
    private array $config = [];
    private array $env = [];
    private bool $loaded = false;
    
    public function __construct()
    {
        $this->loadEnvironment();
        $this->loadConfiguration();
    }
    
    /**
     * Load environment variables from .env file
     */
    private function loadEnvironment(): void
    {
        $envFile = $this->getProjectRoot() . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip comments and empty lines
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (strlen($value) >= 2) {
                        if (($value[0] === '"' && $value[-1] === '"') ||
                            ($value[0] === "'" && $value[-1] === "'")) {
                            $value = substr($value, 1, -1);
                        }
                    }
                    
                    $this->env[$key] = $value;
                }
            }
        }
        
        // Also load from $_ENV for Docker/system environments
        $this->env = array_merge($_ENV, $this->env);
    }
    
    /**
     * Load configuration files
     */
    private function loadConfiguration(): void
    {
        if ($this->loaded) {
            return;
        }
        
        // Load main config file if exists
        $configFile = $this->getProjectRoot() . '/config/adminkit.php';
        
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            // Default configuration
            $this->config = $this->getDefaultConfig();
        }
        
        $this->loaded = true;
    }
    
    /**
     * Get configuration value with dot notation support
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }
        
        $config = $value;
    }
    
    /**
     * Get environment variable with fallback
     */
    public function env(string $key, mixed $default = null): mixed
    {
        $value = $this->env[$key] ?? $default;
        
        // Convert string representations to proper types
        if (is_string($value)) {
            // Boolean conversion
            $lower = strtolower($value);
            if (in_array($lower, ['true', 'false'])) {
                return $lower === 'true';
            }
            
            // Null conversion
            if ($lower === 'null') {
                return null;
            }
            
            // Number conversion
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }
        }
        
        return $value;
    }
    
    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }
    
    /**
     * Get all environment variables
     */
    public function getAllEnv(): array
    {
        return $this->env;
    }
    
    /**
     * Reload configuration from files
     */
    public function reload(): void
    {
        $this->loaded = false;
        $this->config = [];
        $this->env = [];
        
        $this->loadEnvironment();
        $this->loadConfiguration();
    }
    
    /**
     * Get project root directory
     */
    private function getProjectRoot(): string
    {
        // Try to find project root by looking for composer.json or .env
        $path = getcwd();
        
        while ($path !== '/' && $path !== '') {
            if (file_exists($path . '/composer.json') || 
                file_exists($path . '/.env') || 
                file_exists($path . '/config/adminkit.php')) {
                return $path;
            }
            $path = dirname($path);
        }
        
        return getcwd();
    }
    
    /**
     * Get default configuration with environment variable support
     */
    private function getDefaultConfig(): array
    {
        return [
            'app' => [
                'name' => $this->env('APP_NAME', 'AdminKit Panel'),
                'url' => $this->env('APP_URL', 'http://localhost:8000'),
                'timezone' => $this->env('APP_TIMEZONE', 'Europe/Istanbul'),
                'locale' => $this->env('APP_LOCALE', 'tr'),
                'debug' => $this->env('APP_DEBUG', true),
                'key' => $this->env('APP_KEY', ''),
            ],
            
            'database' => [
                'driver' => $this->env('DB_CONNECTION', 'mysql'),
                'host' => $this->env('DB_HOST', 'localhost'),
                'port' => (int) $this->env('DB_PORT', 3306),
                'database' => $this->env('DB_DATABASE', 'adminkit'),
                'username' => $this->env('DB_USERNAME', 'root'),
                'password' => $this->env('DB_PASSWORD', ''),
                'charset' => $this->env('DB_CHARSET', 'utf8mb4'),
                'collation' => $this->env('DB_COLLATION', 'utf8mb4_unicode_ci'),
                'options' => [
                    'ssl_mode' => $this->env('DB_SSL_MODE', 'PREFERRED'),
                    'timeout' => (int) $this->env('DB_TIMEOUT', 30),
                ]
            ],
            
            'auth' => [
                'enabled' => $this->env('AUTH_ENABLED', true),
                'session_timeout' => (int) $this->env('AUTH_SESSION_TIMEOUT', 7200),
                '2fa_enabled' => $this->env('AUTH_2FA_ENABLED', true),
                'password_min_length' => (int) $this->env('AUTH_PASSWORD_MIN_LENGTH', 8),
                'max_login_attempts' => (int) $this->env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
                'lockout_duration' => (int) $this->env('AUTH_LOCKOUT_DURATION', 900),
                'remember_me' => $this->env('AUTH_REMEMBER_ME', true),
                'session_name' => $this->env('AUTH_SESSION_NAME', 'adminkit_session'),
            ],
            
            'cache' => [
                'enabled' => $this->env('CACHE_ENABLED', true),
                'driver' => $this->env('CACHE_DRIVER', 'file'),
                'ttl' => (int) $this->env('CACHE_TTL', 3600),
                'prefix' => $this->env('CACHE_PREFIX', 'adminkit_'),
                'path' => $this->env('CACHE_PATH', 'cache'),
                'redis' => [
                    'host' => $this->env('REDIS_HOST', '127.0.0.1'),
                    'port' => (int) $this->env('REDIS_PORT', 6379),
                    'password' => $this->env('REDIS_PASSWORD', ''),
                    'database' => (int) $this->env('REDIS_DATABASE', 0),
                ]
            ],
            
            'queue' => [
                'enabled' => $this->env('QUEUE_ENABLED', true),
                'driver' => $this->env('QUEUE_CONNECTION', 'database'),
                'table' => $this->env('QUEUE_TABLE', 'jobs'),
                'max_attempts' => (int) $this->env('QUEUE_MAX_ATTEMPTS', 3),
                'retry_delay' => (int) $this->env('QUEUE_RETRY_DELAY', 60),
                'timeout' => (int) $this->env('QUEUE_TIMEOUT', 3600),
                'redis' => [
                    'queue' => $this->env('QUEUE_REDIS_QUEUE', 'default'),
                    'connection' => $this->env('QUEUE_REDIS_CONNECTION', 'default'),
                ]
            ],
            
            'websocket' => [
                'enabled' => $this->env('WEBSOCKET_ENABLED', false),
                'host' => $this->env('WEBSOCKET_HOST', '0.0.0.0'),
                'port' => (int) $this->env('WEBSOCKET_PORT', 8080),
                'ssl' => $this->env('WEBSOCKET_SSL', false),
                'cert_path' => $this->env('WEBSOCKET_CERT_PATH', ''),
                'key_path' => $this->env('WEBSOCKET_KEY_PATH', ''),
            ],
            
            'performance' => [
                'enabled' => $this->env('PERFORMANCE_ENABLED', true),
                'slow_query_threshold' => (int) $this->env('PERFORMANCE_SLOW_QUERY_THRESHOLD', 1000),
                'memory_limit_warning' => (int) $this->env('PERFORMANCE_MEMORY_LIMIT_WARNING', 80),
                'profiling' => $this->env('PERFORMANCE_PROFILING', false),
            ],
            
            'uploads' => [
                'path' => $this->env('UPLOAD_PATH', 'public/uploads'),
                'max_size' => $this->env('UPLOAD_MAX_SIZE', '10M'),
                'allowed_types' => explode(',', $this->env('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,gif,pdf,docx,xlsx')),
                'image_quality' => (int) $this->env('UPLOAD_IMAGE_QUALITY', 85),
                'auto_optimize' => $this->env('UPLOAD_AUTO_OPTIMIZE', true),
            ],
            
            'security' => [
                'csrf_enabled' => $this->env('SECURITY_CSRF_ENABLED', true),
                'xss_protection' => $this->env('SECURITY_XSS_PROTECTION', true),
                'clickjacking_protection' => $this->env('SECURITY_CLICKJACKING_PROTECTION', true),
                'https_only' => $this->env('SECURITY_HTTPS_ONLY', false),
                'rate_limiting' => $this->env('SECURITY_RATE_LIMITING', true),
            ],
            
            'mail' => [
                'mailer' => $this->env('MAIL_MAILER', 'smtp'),
                'host' => $this->env('MAIL_HOST', 'smtp.gmail.com'),
                'port' => (int) $this->env('MAIL_PORT', 587),
                'username' => $this->env('MAIL_USERNAME', ''),
                'password' => $this->env('MAIL_PASSWORD', ''),
                'encryption' => $this->env('MAIL_ENCRYPTION', 'tls'),
                'from_address' => $this->env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'from_name' => $this->env('MAIL_FROM_NAME', $this->env('APP_NAME', 'AdminKit')),
            ],
            
            'logging' => [
                'channel' => $this->env('LOG_CHANNEL', 'single'),
                'level' => $this->env('LOG_LEVEL', 'debug'),
                'path' => $this->env('LOG_PATH', 'logs/adminkit.log'),
                'max_files' => (int) $this->env('LOG_MAX_FILES', 7),
                'daily' => $this->env('LOG_DAILY', true),
            ],
            
            'session' => [
                'lifetime' => (int) $this->env('SESSION_LIFETIME', 120),
                'encrypt' => $this->env('SESSION_ENCRYPT', false),
                'path' => $this->env('SESSION_PATH', '/'),
                'domain' => $this->env('SESSION_DOMAIN', ''),
                'driver' => $this->env('SESSION_DRIVER', 'file'),
                'store' => $this->env('SESSION_STORE', 'sessions'),
            ],
        ];
    }
    
    /**
     * Validate configuration
     */
    public function validate(): array
    {
        $errors = [];
        
        // Required environment variables
        $required = [
            'APP_NAME' => 'Application name is required',
            'APP_URL' => 'Application URL is required',
            'DB_HOST' => 'Database host is required',
            'DB_DATABASE' => 'Database name is required',
            'DB_USERNAME' => 'Database username is required',
        ];
        
        foreach ($required as $key => $message) {
            if (empty($this->env($key))) {
                $errors[] = $message;
            }
        }
        
        // Validate database connection if possible
        if (!empty($this->env('DB_HOST'))) {
            try {
                $this->testDatabaseConnection();
            } catch (\Exception $e) {
                $errors[] = 'Database connection failed: ' . $e->getMessage();
            }
        }
        
        return $errors;
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection(): void
    {
        $db = $this->get('database');
        
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s',
            $db['driver'],
            $db['host'],
            $db['port'],
            $db['database'],
            $db['charset']
        );
        
        new \PDO($dsn, $db['username'], $db['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_TIMEOUT => $db['options']['timeout'] ?? 30,
        ]);
    }
    
    /**
     * Get configuration as array for specific section
     */
    public function section(string $section): array
    {
        return $this->get($section, []);
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function isDebug(): bool
    {
        return (bool) $this->get('app.debug', false);
    }
    
    /**
     * Get application name
     */
    public function getAppName(): string
    {
        return $this->get('app.name', 'AdminKit Panel');
    }
    
    /**
     * Get application URL
     */
    public function getAppUrl(): string
    {
        return $this->get('app.url', 'http://localhost:8000');
    }
    
    /**
     * Get database configuration
     */
    public function getDatabaseConfig(): array
    {
        return $this->section('database');
    }
    
    /**
     * Generate configuration file from current settings
     */
    public function generateConfigFile(string $path = null): string
    {
        $path = $path ?: $this->getProjectRoot() . '/config/adminkit.php';
        
        $config = $this->exportConfigAsPhp();
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, $config);
        
        return $path;
    }
    
    /**
     * Export configuration as PHP code
     */
    private function exportConfigAsPhp(): string
    {
        $config = var_export($this->config, true);
        
        return <<<PHP
<?php

/**
 * AdminKit Configuration
 * 
 * This file is auto-generated. Do not edit directly.
 * Use environment variables (.env) to customize settings.
 */

return {$config};
PHP;
    }
}
