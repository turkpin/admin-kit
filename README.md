# AdminKit v1.0.7

[![Latest Stable Version](https://poser.pugx.org/turkpin/admin-kit/v/stable)](https://packagist.org/packages/turkpin/admin-kit)
[![Total Downloads](https://poser.pugx.org/turkpin/admin-kit/downloads)](https://packagist.org/packages/turkpin/admin-kit)
[![License](https://poser.pugx.org/turkpin/admin-kit/license)](https://packagist.org/packages/turkpin/admin-kit)
[![PHP Version Require](https://poser.pugx.org/turkpin/admin-kit/require/php)](https://packagist.org/packages/turkpin/admin-kit)

**🚀 Complete EasyAdmin alternative - Professional admin panel toolkit for PHP with web-based installation, comprehensive documentation, and production-ready architecture.**

## ✨ Key Features

### 🌐 **Web-Based Installation (NEW in v1.0.7)**
- **5-Step Installation Wizard**: Professional setup experience
- **System Requirements Check**: Automatic validation of dependencies
- **Database Configuration**: Real-time connection testing
- **Admin User Creation**: Secure password strength validation
- **Environment Generation**: Automatic .env file creation
- **Animated Interface**: Modern, responsive installation UI

### 🚀 **Complete EasyAdmin Alternative**
- **Entity CRUD Operations**: Full database management with Doctrine ORM
- **Field Types**: Text, email, password, number, boolean, choice, date, file, image
- **Association Management**: One-to-one, one-to-many, many-to-many relationships
- **Dashboard Widgets**: Counters, charts, lists, custom widgets with real-time data
- **Advanced Filtering**: Search, sort, filter with query builder
- **Batch Operations**: Multiple record operations
- **Permission System**: Role-based access control

### 📚 **Professional Documentation (NEW in v1.0.7)**
- **Getting Started Guide**: Complete quick start tutorial
- **Installation Manual**: Detailed setup for all environments
- **Configuration Reference**: Comprehensive environment and PHP settings
- **API Documentation**: Full method and class reference
- **Code Examples**: Working blog admin panel example
- **Field Types Guide**: Complete field configuration reference

### 🎯 **Demo Application (NEW in v1.0.7)**
- **Interactive Demo**: Working admin panel with sample data
- **Sample Entities**: User, Product, Category with relationships
- **Dashboard Widgets**: Real-time analytics and counters
- **Form Examples**: All field types and validation
- **Access**: `/demo` route with beautiful interface

### 🌍 **Comprehensive Translation System**
- **Multi-language Support**: Complete Turkish and English translations
- **Template Integration**: All hard-coded strings eliminated
- **JavaScript Ready**: Translation objects for frontend
- **Parameter Substitution**: Dynamic content with `:param` syntax
- **Performance Optimized**: Static caching with graceful fallbacks

### 🔧 **Complete CLI Management Suite**
- **Database Migrations**: `migrate`, `migrate --fresh`, `migrate --rollback`
- **User Management**: `user:create`, `user:create --admin`
- **Development Server**: `serve` with configurable host/port
- **Intelligent Installation**: `install` with Docker auto-setup
- **Version Information**: Enhanced feature showcase

### 🐳 **Smart Docker Integration**
- **PHP 8.3 Optimized**: Latest performance and security features
- **One-Command Setup**: `docker-compose up --build -d`
- **Complete Stack**: Nginx, MySQL 8.0, Redis 7, MailHog, Adminer
- **Auto-Configuration**: Environment variables for Docker/local
- **JIT Compilation**: PHP 8.3 JIT enabled for maximum performance

### 🛡️ **Enterprise Security**
- **Apache Security Rules**: .htaccess protection for sensitive files
- **Input Validation**: Comprehensive form validation
- **CSRF Protection**: Built-in security measures
- **Session Security**: Secure cookie configuration
- **Password Strength**: Real-time validation with indicators

### ⚡ **Modern Architecture**
- **Slim 4 Framework**: Fast, modern PHP framework
- **PSR-11 Container**: Dependency injection with PHP-DI
- **Doctrine ORM**: Professional database abstraction
- **Smarty Templates**: Powerful template engine
- **Container-Based**: Fully extensible service architecture

## 🚀 Quick Start

### Installation

```bash
composer require turkpin/admin-kit
```

### Intelligent Setup

```bash
# Install with interactive Docker setup
php vendor/bin/adminkit install

# For Docker environment
php vendor/bin/adminkit install --with-docker
```

### Database Setup

```bash
# Run migrations
php vendor/bin/adminkit migrate

# Create admin user
php vendor/bin/adminkit user:create --admin
```

### Development Server

```bash
# Start development server
php vendor/bin/adminkit serve

# Custom host and port
php vendor/bin/adminkit serve --host=0.0.0.0 --port=8080
```

## 🐳 Docker Deployment

### Quick Start with Docker

```bash
# Clone and setup
git clone https://github.com/turkpin/admin-kit.git
cd admin-kit

# Install with Docker support
php vendor/bin/adminkit install --with-docker

# Start containers
docker-compose up --build -d

# Setup database
docker-compose exec app php vendor/bin/adminkit migrate

# Create admin user
docker-compose exec app php vendor/bin/adminkit user:create --admin
```

### Service Access

- **AdminKit Panel**: http://localhost:8000
- **MailHog (Email Testing)**: http://localhost:8025
- **Adminer (Database)**: http://localhost:8080
- **Redis**: localhost:6379
- **MySQL**: localhost:3306

## 🌍 Internationalization

### Template Usage

```smarty
{* Basic translation *}
{adminkit_translate('welcome')}

{* With parameters *}
{adminkit_translate('total_records', ['count' => $pagination.total_items])}

{* User greeting *}
{adminkit_translate('welcome_message', ['name' => $user->getName()])}
```

### JavaScript Integration

```php
// In your PHP controller
$translations = adminkit_translate_js('tr'); // or 'en'
```

```javascript
// In your JavaScript
const translations = <?php echo adminkit_translate_js(); ?>;
console.log(translations.welcome); // "Hoş Geldiniz!"
```

### Supported Languages

- 🇹🇷 **Turkish (tr)**: Complete native language support
- 🇺🇸 **English (en)**: Full international support
- 🔧 **Extensible**: Easy to add new languages

## 📊 CLI Commands

### Database Management

```bash
# Run pending migrations
php vendor/bin/adminkit migrate

# Fresh database setup
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

# Interactive creation
php vendor/bin/adminkit user:create user@example.com password123
```

### Development Tools

```bash
# Start development server
php vendor/bin/adminkit serve

# Custom configuration
php vendor/bin/adminkit serve --host=127.0.0.1 --port=9000

# Version information
php vendor/bin/adminkit version
```

### Package Management

```bash
# Update AdminKit files
php vendor/bin/adminkit update

# Force reinstall
php vendor/bin/adminkit install --force
```

## 🔧 Configuration

### Environment Variables

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

# Redis Configuration
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Authentication Settings
AUTH_2FA_ENABLED=true
AUTH_SESSION_TIMEOUT=7200
AUTH_PASSWORD_MIN_LENGTH=8
```

### PHP 8.3 Requirements

```json
{
    "require": {
        "php": ">=8.0",
        "ext-pdo": "*",
        "ext-json": "*",
        "ext-mbstring": "*",
        "symfony/console": "^6.0|^7.0",
        "smarty/smarty": "^4.0|^5.0"
    }
}
```

## 🏗️ Architecture

### Service Layer

- **TwoFactorService**: TOTP authentication management
- **NotificationService**: Multi-channel notifications
- **PerformanceService**: System monitoring and optimization
- **QueueService**: Background job processing
- **FilterService**: Advanced query building
- **AssetService**: Resource management and optimization
- **WebSocketService**: Real-time communication
- **DynamicFormService**: Form wizard and validation

### Translation System

- **Static Caching**: Performance-optimized translation loading
- **Parameter Replacement**: Dynamic content injection
- **Fallback Mechanism**: Graceful handling of missing translations
- **JavaScript Integration**: Frontend translation support

### CLI Architecture

- **Symfony Console**: Professional command-line interface
- **Interactive Prompts**: User-friendly setup wizards
- **Error Handling**: Comprehensive validation and feedback
- **Environment Detection**: Smart Docker/local configuration

## 📚 Documentation

- [Installation Guide](docs/installation.md)
- [Quick Start Guide](docs/quick-start.md)
- [Field Types Reference](docs/field-types.md)
- [Services Documentation](docs/services/README.md)
- [Translation System](docs/translation-system.md)
- [CLI Commands](docs/cli-commands.md)

## 🔄 Changelog

### v1.0.6 (2025-01-07)
- **🌍 Comprehensive Translation System**: Complete i18n support with TR/EN languages
- **🔧 Enhanced CLI Suite**: Database migrations, user management, development server
- **🐳 PHP 8.3 Docker Support**: Optimized performance with JIT compilation
- **⚡ Performance Improvements**: OPcache optimization, Redis integration
- **🛡️ Security Enhancements**: Session security, input validation improvements

### v1.0.5 (2025-01-06)
- Enhanced Docker integration
- Intelligent installation process
- Environment auto-configuration

### v1.0.4 (2025-01-05)
- Advanced filtering system
- Performance monitoring
- WebSocket support

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Development Setup

```bash
# Clone repository
git clone https://github.com/turkpin/admin-kit.git
cd admin-kit

# Install dependencies
composer install

# Setup development environment
php vendor/bin/adminkit install --with-docker

# Start development environment
docker-compose up -d
```

## 📄 License

AdminKit is open-sourced software licensed under the [MIT license](LICENSE).

## 🙋‍♂️ Support

- **Issues**: [GitHub Issues](https://github.com/turkpin/admin-kit/issues)
- **Documentation**: [GitHub Wiki](https://github.com/turkpin/admin-kit/wiki)
- **Discussions**: [GitHub Discussions](https://github.com/turkpin/admin-kit/discussions)

## 🌟 Credits

Created and maintained by [Oktay Aydoğan](https://turkpin.com) and the AdminKit community.

---

**⭐ If AdminKit helps your project, please give it a star on GitHub!**
