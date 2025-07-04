# AdminKit v1.0.6 Installation Guide

This guide will walk you through installing AdminKit v1.0.6 with its new translation system, PHP 8.3 support, and enhanced CLI tools.

## 📋 Requirements

### System Requirements
- **PHP**: 8.0 or higher (8.3 recommended for optimal performance)
- **Database**: MySQL 5.7+ or MySQL 8.0+ (recommended)
- **Memory**: Minimum 256MB, 512MB+ recommended
- **Extensions**: PDO, JSON, mbstring (required for i18n)

### Optional Requirements
- **Redis**: For caching and session storage (recommended)
- **Docker**: For containerized deployment
- **Composer**: For package management

## 🚀 Quick Installation

### 1. Install via Composer

```bash
composer require turkpin/admin-kit
```

### 2. Interactive Setup

AdminKit v1.0.6 includes an intelligent installation wizard:

```bash
# Run the interactive installer
php vendor/bin/adminkit install

# With Docker support (recommended)
php vendor/bin/adminkit install --with-docker
```

The installer will:
- ✅ Create necessary directories
- ✅ Publish assets and templates
- ✅ Generate environment configuration
- ✅ Setup Docker files (if requested)
- ✅ Provide step-by-step guidance

### 3. Database Setup

```bash
# Run database migrations
php vendor/bin/adminkit migrate

# Create an admin user
php vendor/bin/adminkit user:create --admin
```

### 4. Start Development Server

```bash
# Start the built-in server
php vendor/bin/adminkit serve

# Access AdminKit at http://localhost:8000
```

## 🐳 Docker Installation (Recommended)

AdminKit v1.0.6 includes optimized Docker support with PHP 8.3 and JIT compilation.

### Quick Docker Setup

```bash
# Clone the repository (or create new project)
git clone https://github.com/turkpin/admin-kit.git my-admin
cd my-admin

# Install with Docker support
php vendor/bin/adminkit install --with-docker

# Start all services
docker-compose up --build -d
```

### Docker Services

The Docker setup includes:

- **AdminKit App** (PHP 8.3-FPM with JIT)
- **Nginx** (Web server)
- **MySQL 8.0** (Database)
- **Redis 7** (Caching & Sessions)
- **MailHog** (Email testing)
- **Adminer** (Database management)

### Service Access Points

| Service | URL | Purpose |
|---------|-----|---------|
| AdminKit | http://localhost:8000 | Main admin panel |
| MailHog | http://localhost:8025 | Email testing interface |
| Adminer | http://localhost:8080 | Database management |
| Redis | localhost:6379 | Cache server |
| MySQL | localhost:3306 | Database server |

### Docker Setup Commands

```bash
# Start containers
docker-compose up -d

# Run migrations inside container
docker-compose exec app php vendor/bin/adminkit migrate

# Create admin user inside container
docker-compose exec app php vendor/bin/adminkit user:create --admin

# View logs
docker-compose logs -f app

# Stop containers
docker-compose down
```

## ⚙️ Manual Installation

For custom setups or when you need more control:

### 1. Directory Structure

Create the following directories in your project:

```
your-project/
├── config/
├── public/
│   └── assets/
│       └── adminkit/
├── templates/
│   └── adminkit/
├── migrations/
├── cache/
├── logs/
└── uploads/
```

### 2. Environment Configuration

Create `.env` file:

```env
# Application Settings
APP_NAME="AdminKit Panel"
APP_URL=http://localhost:8000
APP_DEBUG=true
APP_TIMEZONE=Europe/Istanbul
APP_LOCALE=tr

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adminkit
DB_USERNAME=root
DB_PASSWORD=

# Cache Configuration
CACHE_DRIVER=file
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Authentication Settings
AUTH_2FA_ENABLED=true
AUTH_SESSION_TIMEOUT=7200
AUTH_PASSWORD_MIN_LENGTH=8
```

### 3. Publish Assets

```bash
# Publish all assets
php vendor/bin/adminkit install --skip-docker

# Or publish individually
php vendor/bin/adminkit install --skip-env --skip-config
```

### 4. Database Setup

```bash
# Create database manually, then run migrations
php vendor/bin/adminkit migrate

# For fresh install
php vendor/bin/adminkit migrate --fresh
```

## 🌍 Translation Configuration

AdminKit v1.0.6 includes a comprehensive translation system.

### Supported Languages

- **Turkish (tr)**: Complete native support
- **English (en)**: Full international support

### Language Configuration

Set your preferred language in `.env`:

```env
APP_LOCALE=tr  # or 'en'
```

### Template Usage

Templates automatically use the translation system:

```smarty
{* Basic translation *}
<h1>{adminkit_translate('welcome')}</h1>

{* With parameters *}
<p>{adminkit_translate('total_records', ['count' => $total])}</p>

{* User-specific content *}
<span>{adminkit_translate('welcome_message', ['name' => $user.name])}</span>
```

### JavaScript Integration

For frontend translations:

```php
// In your controller
$jsTranslations = adminkit_translate_js();
```

```javascript
// In your template
const translations = <?php echo adminkit_translate_js(); ?>;
alert(translations.welcome); // Shows localized welcome message
```

## 🔧 CLI Commands Reference

AdminKit v1.0.6 includes a comprehensive CLI suite:

### Installation & Management

```bash
# Interactive installation
php vendor/bin/adminkit install

# Force reinstall
php vendor/bin/adminkit install --force

# Update package files
php vendor/bin/adminkit update

# Show version information
php vendor/bin/adminkit version
```

### Database Management

```bash
# Run pending migrations
php vendor/bin/adminkit migrate

# Fresh database (drops all tables)
php vendor/bin/adminkit migrate --fresh

# Rollback last migration
php vendor/bin/adminkit migrate --rollback
```

### User Management

```bash
# Create regular user
php vendor/bin/adminkit user:create

# Create admin user
php vendor/bin/adminkit user:create --admin

# Create user with specific credentials
php vendor/bin/adminkit user:create admin@example.com mypassword --admin
```

### Development Tools

```bash
# Start development server
php vendor/bin/adminkit serve

# Custom host and port
php vendor/bin/adminkit serve --host=0.0.0.0 --port=9000
```

## 🛠️ PHP 8.3 Optimization

AdminKit v1.0.6 is optimized for PHP 8.3 with JIT compilation.

### Recommended PHP Configuration

```ini
; Performance
opcache.enable=1
opcache.jit_buffer_size=64M
opcache.jit=tracing
memory_limit=256M

; Security
expose_php=Off
allow_url_fopen=Off

; Sessions (if using Redis)
session.save_handler=redis
session.save_path="tcp://127.0.0.1:6379"

; Internationalization
default_charset=UTF-8
mbstring.internal_encoding=UTF-8
```

### Docker Optimization

The included Docker configuration automatically provides:

- ✅ PHP 8.3 with JIT enabled
- ✅ OPcache optimization
- ✅ Redis session handling
- ✅ Secure configuration
- ✅ Performance monitoring

## 🔍 Troubleshooting

### Common Issues

#### Permission Errors
```bash
# Fix file permissions
chmod -R 755 storage/ public/uploads/
chown -R www-data:www-data storage/ public/uploads/
```

#### Database Connection
```bash
# Test database connection
php vendor/bin/adminkit migrate --dry-run
```

#### Missing Extensions
```bash
# Check required extensions
php -m | grep -E "(pdo|json|mbstring)"
```

#### Docker Issues
```bash
# Rebuild containers
docker-compose down
docker-compose up --build -d

# Check logs
docker-compose logs app
```

### Getting Help

- **Documentation**: [GitHub Wiki](https://github.com/turkpin/admin-kit/wiki)
- **Issues**: [GitHub Issues](https://github.com/turkpin/admin-kit/issues)
- **Discussions**: [GitHub Discussions](https://github.com/turkpin/admin-kit/discussions)

## 🎯 Next Steps

After installation:

1. **Configure your entities** in `config/adminkit.php`
2. **Customize templates** in `templates/adminkit/`
3. **Set up translations** for additional languages
4. **Configure authentication** and user roles
5. **Optimize performance** settings for production

## 📚 Related Documentation

- [Quick Start Guide](quick-start.md)
- [Translation System](translation-system.md)
- [CLI Commands](cli-commands.md)
- [Docker Guide](docker-guide.md)
- [Configuration Reference](configuration.md)

---

**Need help?** Check our [troubleshooting guide](troubleshooting.md) or [open an issue](https://github.com/turkpin/admin-kit/issues).
