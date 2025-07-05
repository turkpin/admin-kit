# Installation Guide

This comprehensive guide covers all installation methods for AdminKit.

## System Requirements

### Minimum Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 5.7+ or **MariaDB**: 10.2+
- **Composer**: Latest version
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: 512MB minimum, 1GB recommended
- **Disk Space**: 100MB for core files, additional space for uploads and cache

### Required PHP Extensions

AdminKit requires the following PHP extensions:

- `pdo` - Database abstraction layer
- `pdo_mysql` - MySQL driver for PDO
- `json` - JSON support
- `mbstring` - Multibyte string support
- `zip` - Archive support
- `curl` - HTTP client (recommended)
- `gd` or `imagick` - Image processing (recommended)
- `intl` - Internationalization (recommended)

### Optional Extensions for Enhanced Features

- `redis` - For caching and sessions
- `memcached` - Alternative caching driver
- `opcache` - PHP opcode caching for better performance
- `apcu` - User cache for improved performance

## Installation Methods

### Method 1: Web-Based Installation (Recommended)

The easiest way to install AdminKit is through the web-based installation wizard.

#### Step 1: Download or Create Project

**Option A: Create new project**
```bash
composer create-project turkpin/admin-kit my-admin-panel
cd my-admin-panel
```

**Option B: Add to existing project**
```bash
composer require turkpin/admin-kit
```

#### Step 2: Web Server Setup

**Apache Configuration**

AdminKit includes `.htaccess` files for Apache. Ensure `mod_rewrite` is enabled:

```apache
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Virtual host example:
```apache
<VirtualHost *:80>
    ServerName admin.example.com
    DocumentRoot /path/to/your/project/public
    
    <Directory /path/to/your/project/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/adminkit_error.log
    CustomLog ${APACHE_LOG_DIR}/adminkit_access.log combined
</VirtualHost>
```

**Nginx Configuration**

```nginx
server {
    listen 80;
    server_name admin.example.com;
    root /path/to/your/project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
    
    # Block access to sensitive files
    location ~ /(\.env|config/|src/|vendor/|composer\.(json|lock))$ {
        deny all;
    }
}
```

#### Step 3: Access Installation Wizard

1. Navigate to your domain in a browser
2. AdminKit will automatically redirect to `/install`
3. Follow the installation steps:

**Step 1: Welcome**
- Introduction to AdminKit features
- Overview of what will be installed

**Step 2: System Requirements**
- Automatic check of PHP version
- Verification of required extensions
- Directory permissions check
- Composer dependencies validation

**Step 3: Database Configuration**
- Database connection setup
- Database creation (if needed)
- Connection testing

**Step 4: Admin User Setup**
- Create administrator account
- Set secure password
- Configure user details

**Step 5: Installation Complete**
- Summary of installation
- Links to admin panel and documentation
- Security recommendations

### Method 2: CLI Installation

For advanced users or automated deployments.

#### Step 1: Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

#### Step 2: Environment Configuration

Copy and configure environment file:

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
# Application Settings
APP_NAME="My Admin Panel"
APP_URL=https://admin.example.com
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_LOCALE=en

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=adminkit_production
DB_USERNAME=adminkit_user
DB_PASSWORD=secure_password_here

# Security
SESSION_NAME=adminkit_session
SESSION_TIMEOUT=7200
PASSWORD_MIN_LENGTH=12

# AdminKit Configuration
ADMINKIT_ROUTE_PREFIX=/admin
ADMINKIT_BRAND_NAME="My Admin Panel"

# Cache Configuration (Redis recommended for production)
CACHE_ENABLED=true
CACHE_PREFIX=adminkit_prod
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Performance
SMARTY_CACHING=true
SMARTY_CACHE_LIFETIME=3600
```

#### Step 3: Database Setup

Run migrations:

```bash
php bin/adminkit migrate
```

For fresh installation with sample data:

```bash
php bin/adminkit migrate --fresh --seed
```

#### Step 4: Create Admin User

```bash
php bin/adminkit user:create --admin admin@example.com secure_password "Administrator"
```

#### Step 5: Set Permissions

```bash
# Make cache and upload directories writable
chmod -R 755 var/
chmod -R 755 public/uploads/

# Secure sensitive files
chmod 600 .env
```

### Method 3: Docker Installation

Perfect for development and consistent deployments.

#### Step 1: Clone Repository

```bash
git clone https://github.com/oktayaydogan/admin-kit.git
cd admin-kit
```

#### Step 2: Docker Setup

```bash
# Install with Docker support
php vendor/bin/adminkit install --with-docker

# Start containers
docker-compose up -d

# Setup database
docker-compose exec app php bin/adminkit migrate

# Create admin user
docker-compose exec app php bin/adminkit user:create --admin admin@example.com password "Admin User"
```

#### Step 3: Access Application

- **AdminKit Panel**: http://localhost:8000
- **MailHog (Email Testing)**: http://localhost:8025
- **Adminer (Database)**: http://localhost:8080

## Post-Installation Configuration

### Security Hardening

#### 1. Secure File Permissions

```bash
# Application files
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Executable files
chmod +x bin/adminkit

# Sensitive files
chmod 600 .env
chmod 600 config/*.php

# Writable directories
chmod 755 var/ public/uploads/
```

#### 2. Remove Installation Files

For production, remove or secure installation directory:

```bash
# Option 1: Remove completely
rm -rf install/

# Option 2: Deny web access (Apache)
echo "Deny from all" > install/.htaccess

# Option 3: Deny web access (Nginx)
# Add to nginx config:
# location ^~ /install/ { deny all; }
```

#### 3. Configure SSL/TLS

Always use HTTPS in production:

```nginx
server {
    listen 443 ssl http2;
    ssl_certificate /path/to/certificate.pem;
    ssl_certificate_key /path/to/private.key;
    
    # Additional SSL configuration...
}
```

#### 4. Database Security

- Use dedicated database user with minimal permissions
- Enable SSL for database connections
- Regular backups
- Monitor for suspicious activity

### Performance Optimization

#### 1. PHP Configuration

Optimize `php.ini`:

```ini
; Memory and execution
memory_limit = 256M
max_execution_time = 300

; OPcache (highly recommended)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0

; File uploads
upload_max_filesize = 32M
post_max_size = 32M
```

#### 2. Caching

Enable caching in production:

```env
CACHE_ENABLED=true
SMARTY_CACHING=true
```

#### 3. Asset Optimization

```bash
# Minify and compress assets
php bin/adminkit assets:compile --minify

# Enable gzip compression in web server
```

### Troubleshooting

#### Common Installation Issues

**1. Composer Installation Fails**

```bash
# Clear composer cache
composer clear-cache

# Install with verbose output
composer install -vvv

# Check PHP version
php -v
```

**2. Database Connection Issues**

```bash
# Test database connection
php bin/adminkit db:test

# Check MySQL service
sudo systemctl status mysql

# View MySQL error logs
sudo tail -f /var/log/mysql/error.log
```

**3. Permission Errors**

```bash
# Fix ownership
sudo chown -R www-data:www-data /path/to/adminkit

# Fix permissions
sudo chmod -R 755 var/ public/uploads/
```

**4. 500 Internal Server Error**

```bash
# Check web server error logs
sudo tail -f /var/log/apache2/error.log
# or
sudo tail -f /var/log/nginx/error.log

# Enable debug mode
echo "APP_DEBUG=true" >> .env
```

#### Getting Help

If you encounter issues:

1. Check the [troubleshooting section](getting-started.md#troubleshooting)
2. Search [existing issues](https://github.com/oktayaydogan/admin-kit/issues)
3. Create a new issue with:
   - PHP version and extensions
   - Web server configuration
   - Error messages and logs
   - Steps to reproduce

## Next Steps

After successful installation:

1. **Configure your entities** - See [Getting Started](getting-started.md)
2. **Customize the interface** - Check [Configuration](configuration.md)
3. **Set up backups** - Implement regular database and file backups
4. **Monitor performance** - Use tools like New Relic or similar
5. **Plan for scaling** - Consider load balancing and caching strategies

## Upgrading

To upgrade AdminKit to a newer version:

```bash
# Backup your database and files
php bin/adminkit backup:create

# Update composer dependencies
composer update turkpin/admin-kit

# Run any new migrations
php bin/adminkit migrate

# Clear caches
php bin/adminkit cache:clear
```

Always test upgrades in a staging environment first!
