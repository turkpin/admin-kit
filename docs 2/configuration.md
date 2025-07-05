# Configuration Reference

This guide covers all configuration options available in AdminKit.

## Environment Configuration

AdminKit uses environment variables for configuration. Create a `.env` file in your project root:

### Application Settings

```env
# Application Identity
APP_NAME="My Admin Panel"           # Application name displayed in UI
APP_URL=https://admin.example.com   # Base URL for the application
APP_DEBUG=false                     # Enable/disable debug mode
APP_TIMEZONE=UTC                    # Default timezone
APP_LOCALE=en                       # Default language (en, tr, de)

# Environment
APP_ENV=production                  # Environment (development, production, testing)
```

### Database Configuration

```env
# Database Connection
DB_DRIVER=pdo_mysql                 # Database driver (pdo_mysql, pdo_pgsql, pdo_sqlite)
DB_HOST=localhost                   # Database host
DB_PORT=3306                        # Database port
DB_DATABASE=adminkit                # Database name
DB_USERNAME=adminkit_user           # Database username
DB_PASSWORD=secure_password         # Database password
DB_CHARSET=utf8mb4                  # Character set
DB_COLLATION=utf8mb4_unicode_ci     # Collation

# Connection Pool (Optional)
DB_POOL_SIZE=10                     # Maximum connections
DB_IDLE_TIMEOUT=300                 # Idle connection timeout (seconds)
```

### Cache Configuration

```env
# Cache Settings
CACHE_ENABLED=true                  # Enable/disable caching
CACHE_PREFIX=adminkit               # Cache key prefix
CACHE_DRIVER=redis                  # Cache driver (redis, memcached, file, array)

# Redis Configuration
REDIS_HOST=127.0.0.1               # Redis host
REDIS_PORT=6379                    # Redis port
REDIS_PASSWORD=                    # Redis password (if required)
REDIS_DATABASE=0                   # Redis database number

# Memcached Configuration (if using memcached)
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
```

### Session Configuration

```env
# Session Management
SESSION_NAME=adminkit_session       # Session cookie name
SESSION_TIMEOUT=7200               # Session timeout (seconds)
SESSION_DRIVER=redis               # Session driver (redis, file, database)
SESSION_SECURE=true                # Secure cookies (HTTPS only)
SESSION_HTTP_ONLY=true             # HTTP-only cookies
```

### Security Configuration

```env
# Authentication
PASSWORD_MIN_LENGTH=8              # Minimum password length
MAX_LOGIN_ATTEMPTS=5               # Maximum failed login attempts
LOCKOUT_DURATION=900               # Account lockout duration (seconds)
TOKEN_LIFETIME=3600                # API token lifetime (seconds)

# Two-Factor Authentication
TWO_FACTOR_ENABLED=true            # Enable 2FA
TWO_FACTOR_ISSUER="AdminKit"       # 2FA issuer name
BACKUP_CODES_COUNT=8               # Number of backup codes

# CSRF Protection
CSRF_TOKEN_NAME=csrf_token         # CSRF token field name
CSRF_TOKEN_LIFETIME=3600           # CSRF token lifetime (seconds)
```

### AdminKit Specific Configuration

```env
# Interface Settings
ADMINKIT_ROUTE_PREFIX=/admin       # URL prefix for admin routes
ADMINKIT_BRAND_NAME="AdminKit"     # Brand name in interface
ADMINKIT_THEME=default             # UI theme
ADMINKIT_ITEMS_PER_PAGE=20         # Default pagination size

# Features
ADMINKIT_ENABLE_SEARCH=true        # Enable global search
ADMINKIT_ENABLE_FILTERS=true       # Enable entity filters
ADMINKIT_ENABLE_EXPORT=true        # Enable data export
ADMINKIT_ENABLE_IMPORT=true        # Enable data import
ADMINKIT_ENABLE_AUDIT=true         # Enable audit logging
```

### Template Configuration

```env
# Smarty Template Engine
SMARTY_CACHING=false               # Enable template caching
SMARTY_CACHE_LIFETIME=3600         # Cache lifetime (seconds)
SMARTY_COMPILE_CHECK=true          # Check for template changes
SMARTY_FORCE_COMPILE=false         # Force template recompilation
SMARTY_DEBUGGING=false             # Enable Smarty debugging
```

### File Upload Configuration

```env
# Upload Settings
UPLOAD_PATH=public/uploads         # Upload directory
UPLOAD_MAX_SIZE=32M                # Maximum file size
UPLOAD_ALLOWED_TYPES="jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx"
UPLOAD_ENABLE_CHUNKED=true         # Enable chunked uploads
UPLOAD_CHUNK_SIZE=1M               # Chunk size for large files

# Image Processing
IMAGE_MAX_WIDTH=2048               # Maximum image width
IMAGE_MAX_HEIGHT=2048              # Maximum image height
IMAGE_QUALITY=85                   # JPEG quality (1-100)
IMAGE_ENABLE_THUMBNAILS=true       # Generate thumbnails
THUMBNAIL_SIZES="150x150,300x300"  # Thumbnail dimensions
```

### Email Configuration

```env
# Mail Settings
MAIL_DRIVER=smtp                   # Mail driver (smtp, sendmail, mailgun, ses)
MAIL_HOST=localhost                # SMTP host
MAIL_PORT=587                      # SMTP port
MAIL_USERNAME=                     # SMTP username
MAIL_PASSWORD=                     # SMTP password
MAIL_ENCRYPTION=tls                # Encryption (tls, ssl)
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="AdminKit"
```

### Logging Configuration

```env
# Logging
LOG_LEVEL=warning                  # Log level (debug, info, warning, error)
LOG_PATH=var/logs                  # Log directory
LOG_MAX_FILES=30                   # Maximum log files to keep
LOG_CHANNEL=daily                  # Log channel (single, daily, weekly)

# Error Reporting
ERROR_REPORTING=true               # Enable error reporting
ERROR_EMAIL=admin@example.com      # Email for error notifications
```

## PHP Configuration

AdminKit requires specific PHP configuration for optimal performance:

### php.ini Settings

```ini
; Memory and Execution
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

; File Uploads
file_uploads = On
upload_max_filesize = 32M
post_max_size = 32M
max_file_uploads = 100

; Session Configuration
session.gc_maxlifetime = 7200
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1

; OPcache (Recommended for Production)
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
```

## AdminKit PHP Configuration

### Entity Configuration

Configure entities programmatically:

```php
<?php

use AdminKit\AdminKit;

$adminKit = new AdminKit($app, [
    'route_prefix' => '/admin',
    'brand_name' => 'My Admin Panel',
    'pagination_limit' => 25,
    'theme' => 'dark',
    'locale' => 'en',
]);

// Add entity with full configuration
$adminKit->addEntity(\App\Entity\Product::class, [
    'title' => 'Products',
    'description' => 'Manage your product catalog',
    'icon' => 'box',
    
    // Field configuration
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Product Name',
            'required' => true,
            'help' => 'Enter the product name',
            'placeholder' => 'e.g., iPhone 13 Pro',
        ],
        'price' => [
            'type' => 'number',
            'label' => 'Price',
            'required' => true,
            'min' => 0,
            'step' => 0.01,
            'currency' => 'USD',
        ],
        'description' => [
            'type' => 'textarea',
            'label' => 'Description',
            'rows' => 5,
            'maxlength' => 1000,
        ],
        'category' => [
            'type' => 'association',
            'label' => 'Category',
            'target_entity' => \App\Entity\Category::class,
            'choice_label' => 'name',
            'required' => true,
        ],
        'image' => [
            'type' => 'image',
            'label' => 'Product Image',
            'upload_dir' => 'products',
            'max_size' => '5MB',
            'allowed_types' => ['jpg', 'jpeg', 'png'],
        ],
        'isActive' => [
            'type' => 'boolean',
            'label' => 'Active',
            'default' => true,
        ],
        'tags' => [
            'type' => 'collection',
            'label' => 'Tags',
            'allow_add' => true,
            'allow_delete' => true,
        ],
    ],
    
    // List configuration
    'list' => [
        'fields' => ['name', 'price', 'category', 'isActive'],
        'sort' => ['name' => 'ASC'],
        'filters' => ['category', 'isActive'],
        'search' => ['name', 'description'],
        'actions' => ['show', 'edit', 'delete'],
        'batch_actions' => ['delete', 'activate', 'deactivate'],
    ],
    
    // Form configuration
    'form' => [
        'fields' => ['name', 'price', 'description', 'category', 'image', 'isActive', 'tags'],
        'groups' => [
            'basic' => ['name', 'price', 'category'],
            'details' => ['description', 'image', 'tags'],
            'settings' => ['isActive'],
        ],
    ],
    
    // Permissions
    'permissions' => [
        'list' => ['ROLE_USER'],
        'show' => ['ROLE_USER'],
        'new' => ['ROLE_ADMIN'],
        'edit' => ['ROLE_ADMIN'],
        'delete' => ['ROLE_SUPER_ADMIN'],
    ],
    
    // Actions
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
    'custom_actions' => [
        'duplicate' => [
            'label' => 'Duplicate',
            'icon' => 'copy',
            'handler' => 'App\\Controller\\ProductController::duplicate',
        ],
    ],
]);
```

### Dashboard Widgets

Configure dashboard widgets:

```php
// Simple counter widget
$adminKit->addDashboardWidget('user_count', [
    'title' => 'Total Users',
    'type' => 'counter',
    'value' => function() use ($entityManager) {
        return $entityManager->getRepository(User::class)->count([]);
    },
    'icon' => 'users',
    'color' => 'blue',
    'link' => '/admin/users',
]);

// Chart widget
$adminKit->addDashboardWidget('sales_chart', [
    'title' => 'Sales This Month',
    'type' => 'chart',
    'chart_type' => 'line',
    'data' => function() {
        // Return chart data
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => [12, 19, 3, 5, 2],
                    'borderColor' => 'rgb(75, 192, 192)',
                ]
            ]
        ];
    },
    'height' => 300,
]);

// Custom widget
$adminKit->addDashboardWidget('recent_orders', [
    'title' => 'Recent Orders',
    'type' => 'list',
    'template' => 'widgets/recent_orders.tpl',
    'data' => function() use ($entityManager) {
        return $entityManager->getRepository(Order::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);
    },
    'refresh_interval' => 30, // seconds
]);
```

### Menu Configuration

Configure navigation menu:

```php
$adminKit->configureMenu([
    [
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'icon' => 'home',
    ],
    [
        'label' => 'Catalog',
        'icon' => 'box',
        'children' => [
            [
                'label' => 'Products',
                'route' => 'products_index',
                'icon' => 'package',
                'permission' => 'product.list',
            ],
            [
                'label' => 'Categories',
                'route' => 'categories_index',
                'icon' => 'folder',
            ],
        ],
    ],
    [
        'label' => 'Users',
        'route' => 'users_index',
        'icon' => 'users',
        'permission' => 'user.list',
        'badge' => function() use ($entityManager) {
            return $entityManager->getRepository(User::class)
                ->count(['isActive' => false]);
        },
    ],
    [
        'label' => 'Settings',
        'icon' => 'settings',
        'children' => [
            [
                'label' => 'System',
                'route' => 'settings_system',
                'permission' => 'admin',
            ],
            [
                'label' => 'Users & Roles',
                'route' => 'settings_users',
                'permission' => 'admin',
            ],
        ],
    ],
]);
```

## Web Server Configuration

### Apache Configuration

```apache
<VirtualHost *:443>
    ServerName admin.example.com
    DocumentRoot /var/www/adminkit/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Compression
    LoadModule deflate_module modules/mod_deflate.so
    <Location />
        SetOutputFilter DEFLATE
        SetEnvIfNoCase Request_URI \
            \.(?:gif|jpe?g|png)$ no-gzip dont-vary
        SetEnvIfNoCase Request_URI \
            \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
    </Location>
    
    # Cache Control
    <FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 month"
    </FilesMatch>
    
    <Directory /var/www/adminkit/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name admin.example.com;
    root /var/www/adminkit/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384;

    # Security Headers
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options DENY always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Cache Control
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }

    # PHP Processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_param PHP_VALUE "open_basedir=$document_root:/tmp:/var/lib/php/sessions";
    }

    # URL Rewriting
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Block sensitive files
    location ~ /\. { deny all; }
    location ~ /(vendor|config|src|install)/ { deny all; }
    location ~ composer\.(json|lock)$ { deny all; }
    location ~ \.env$ { deny all; }
}
```

## Production Deployment

### Environment-Specific Configuration

**Production (.env.production)**
```env
APP_ENV=production
APP_DEBUG=false
CACHE_ENABLED=true
SMARTY_CACHING=true
LOG_LEVEL=warning
SESSION_SECURE=true
```

**Staging (.env.staging)**
```env
APP_ENV=staging
APP_DEBUG=true
CACHE_ENABLED=true
SMARTY_CACHING=false
LOG_LEVEL=info
```

**Development (.env.development)**
```env
APP_ENV=development
APP_DEBUG=true
CACHE_ENABLED=false
SMARTY_CACHING=false
LOG_LEVEL=debug
```

### Configuration Validation

AdminKit includes configuration validation:

```bash
# Validate configuration
php bin/adminkit config:validate

# Show current configuration
php bin/adminkit config:show

# Test database connection
php bin/adminkit db:test

# Clear all caches
php bin/adminkit cache:clear
```

## Troubleshooting Configuration

### Common Configuration Issues

1. **Environment variables not loading**
   - Check `.env` file exists and is readable
   - Verify file permissions (644)
   - Ensure no syntax errors in `.env`

2. **Database connection fails**
   - Verify database credentials
   - Check database server is running
   - Test connection manually

3. **Cache not working**
   - Check Redis/Memcached server status
   - Verify cache directory permissions
   - Test cache connection

4. **Session issues**
   - Check session storage configuration
   - Verify session directory permissions
   - Test session storage connection

5. **File upload problems**
   - Check upload directory permissions
   - Verify PHP upload settings
   - Check disk space

For additional help, see the [Installation Guide](installation.md) or [Getting Started](getting-started.md) documentation.
